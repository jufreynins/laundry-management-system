<?php

namespace Tests\Feature\Phase9;

use App\Enums\PaymentStatus;
use App\Models\Location;
use App\Models\Order;
use App\Services\OnlinePayment\PaymentProvider;
use App\Services\OnlinePayment\StubPaymentProvider;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private function orderWithBalance(Location $location, string $total = '100.00'): Order
    {
        return Order::factory()->create([
            'location_id' => $location->id,
            'total' => $total,
            'amount_paid' => '0.00',
            'balance_due' => $total,
        ]);
    }

    private function initiateCheckout(Order $order): array
    {
        return app(PaymentService::class)->initiateOnlineCheckout($order, app(PaymentProvider::class));
    }

    public function test_valid_signed_success_webhook_completes_payment_and_updates_balance(): void
    {
        $location = Location::factory()->create();
        $order = $this->orderWithBalance($location, '80.00');
        $result = $this->initiateCheckout($order);

        $provider = app(StubPaymentProvider::class);
        $payload = json_encode([
            'type' => 'payment_succeeded',
            'provider_transaction_id' => $result['payment']->provider_transaction_id,
            'payment_method_brand' => 'visa',
            'last_four' => '4242',
        ]);

        $response = $this->post(route('webhooks.payments'), [
            'payload' => $payload,
            'signature' => $provider->sign($payload),
        ]);

        $response->assertOk();
        $this->assertEquals(PaymentStatus::COMPLETED, $result['payment']->fresh()->status);
        $this->assertEquals('80.00', (string) $order->fresh()->amount_paid);
        $this->assertEquals('0.00', (string) $order->fresh()->balance_due);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $location = Location::factory()->create();
        $order = $this->orderWithBalance($location);
        $result = $this->initiateCheckout($order);

        $payload = json_encode([
            'type' => 'payment_succeeded',
            'provider_transaction_id' => $result['payment']->provider_transaction_id,
        ]);

        $response = $this->post(route('webhooks.payments'), [
            'payload' => $payload,
            'signature' => 'totally-invalid-signature',
        ]);

        $response->assertStatus(400);
        $this->assertEquals(PaymentStatus::PENDING, $result['payment']->fresh()->status);
    }

    public function test_missing_signature_is_rejected(): void
    {
        $response = $this->post(route('webhooks.payments'), [
            'payload' => json_encode(['type' => 'payment_succeeded', 'provider_transaction_id' => 'abc']),
        ]);

        $response->assertStatus(400);
    }

    public function test_duplicate_webhook_delivery_is_idempotent(): void
    {
        $location = Location::factory()->create();
        $order = $this->orderWithBalance($location, '80.00');
        $result = $this->initiateCheckout($order);

        $provider = app(StubPaymentProvider::class);
        $payload = json_encode([
            'type' => 'payment_succeeded',
            'provider_transaction_id' => $result['payment']->provider_transaction_id,
        ]);
        $signature = $provider->sign($payload);

        $this->post(route('webhooks.payments'), ['payload' => $payload, 'signature' => $signature]);
        $this->post(route('webhooks.payments'), ['payload' => $payload, 'signature' => $signature]);
        $this->post(route('webhooks.payments'), ['payload' => $payload, 'signature' => $signature]);

        $order->refresh();
        $this->assertEquals('80.00', (string) $order->amount_paid);
        $this->assertEquals('0.00', (string) $order->balance_due);
    }

    public function test_payment_failed_webhook_marks_payment_failed_without_touching_balance(): void
    {
        $location = Location::factory()->create();
        $order = $this->orderWithBalance($location, '80.00');
        $result = $this->initiateCheckout($order);

        $provider = app(StubPaymentProvider::class);
        $payload = json_encode([
            'type' => 'payment_failed',
            'provider_transaction_id' => $result['payment']->provider_transaction_id,
        ]);

        $response = $this->post(route('webhooks.payments'), [
            'payload' => $payload,
            'signature' => $provider->sign($payload),
        ]);

        $response->assertOk();
        $this->assertEquals(PaymentStatus::FAILED, $result['payment']->fresh()->status);
        $this->assertEquals('80.00', (string) $order->fresh()->balance_due);
    }

    public function test_missing_provider_transaction_id_returns_400(): void
    {
        $provider = app(StubPaymentProvider::class);
        $payload = json_encode(['type' => 'payment_succeeded']);

        $response = $this->post(route('webhooks.payments'), [
            'payload' => $payload,
            'signature' => $provider->sign($payload),
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_endpoint_requires_no_authentication(): void
    {
        $location = Location::factory()->create();
        $order = $this->orderWithBalance($location);
        $result = $this->initiateCheckout($order);

        $provider = app(StubPaymentProvider::class);
        $payload = json_encode([
            'type' => 'payment_succeeded',
            'provider_transaction_id' => $result['payment']->provider_transaction_id,
        ]);

        // No actingAs() call — this must work as a guest, like a real provider callback.
        $response = $this->post(route('webhooks.payments'), [
            'payload' => $payload,
            'signature' => $provider->sign($payload),
        ]);

        $response->assertOk();
    }

    public function test_successful_webhook_creates_audit_log(): void
    {
        $location = Location::factory()->create();
        $order = $this->orderWithBalance($location);
        $result = $this->initiateCheckout($order);

        $provider = app(StubPaymentProvider::class);
        $payload = json_encode([
            'type' => 'payment_succeeded',
            'provider_transaction_id' => $result['payment']->provider_transaction_id,
        ]);

        $this->post(route('webhooks.payments'), [
            'payload' => $payload,
            'signature' => $provider->sign($payload),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Payment',
            'model_id' => $result['payment']->id,
            'action' => 'payment_recorded',
        ]);
    }

    public function test_unknown_provider_transaction_id_does_not_error(): void
    {
        $provider = app(StubPaymentProvider::class);
        $payload = json_encode([
            'type' => 'payment_succeeded',
            'provider_transaction_id' => 'nonexistent-transaction-id',
        ]);

        $response = $this->post(route('webhooks.payments'), [
            'payload' => $payload,
            'signature' => $provider->sign($payload),
        ]);

        $response->assertOk();
    }
}
