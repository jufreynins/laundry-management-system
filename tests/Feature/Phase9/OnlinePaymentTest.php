<?php

namespace Tests\Feature\Phase9;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\OnlinePayment\PaymentProvider;
use App\Services\PaymentException;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlinePaymentTest extends TestCase
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

    public function test_initiating_checkout_creates_pending_payment(): void
    {
        $location = Location::factory()->create();
        $order = $this->orderWithBalance($location, '75.00');

        $result = app(PaymentService::class)->initiateOnlineCheckout($order, app(PaymentProvider::class));

        $this->assertEquals(PaymentStatus::PENDING, $result['payment']->status);
        $this->assertEquals(PaymentMethod::ONLINE_CARD, $result['payment']->method);
        $this->assertEquals('75.00', (string) $result['payment']->amount);
        $this->assertNotEmpty($result['checkout_url']);
    }

    public function test_pending_checkout_does_not_affect_order_balance(): void
    {
        $location = Location::factory()->create();
        $order = $this->orderWithBalance($location, '75.00');

        app(PaymentService::class)->initiateOnlineCheckout($order, app(PaymentProvider::class));

        $order->refresh();
        $this->assertEquals('75.00', (string) $order->balance_due);
        $this->assertEquals('0.00', (string) $order->amount_paid);
    }

    public function test_cannot_initiate_checkout_with_zero_balance(): void
    {
        $location = Location::factory()->create();
        $order = $this->orderWithBalance($location, '0.00');

        $this->expectException(PaymentException::class);
        app(PaymentService::class)->initiateOnlineCheckout($order, app(PaymentProvider::class));
    }

    public function test_http_initiate_checkout_redirects_to_hosted_checkout(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);

        $response = $this->actingAs($user)->post(route('online-payments.store', $order));

        $response->assertRedirect();
        $this->assertStringContainsString('/online-payments/checkout/', $response->headers->get('Location'));
    }

    public function test_accountant_cannot_initiate_online_checkout(): void
    {
        $location = Location::factory()->create();
        $accountant = User::factory()->create(['role' => UserRole::ACCOUNTANT, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);

        $response = $this->actingAs($accountant)->post(route('online-payments.store', $order));

        $response->assertForbidden();
    }

    public function test_user_from_different_location_cannot_initiate_checkout(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id]);
        $order = $this->orderWithBalance($location2);

        $response = $this->actingAs($user)->post(route('online-payments.store', $order));

        $response->assertForbidden();
    }

    public function test_online_card_method_cannot_be_submitted_via_manual_payment_endpoint(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);

        $response = $this->actingAs($user)->post(route('payments.store', $order), [
            'amount' => '50.00',
            'method' => PaymentMethod::ONLINE_CARD->value,
            'idempotency_key' => 'attempt-online-manual',
        ]);

        $response->assertSessionHasErrors('method');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_stub_checkout_page_loads_for_pending_payment(): void
    {
        $location = Location::factory()->create();
        $order = $this->orderWithBalance($location);
        $result = app(PaymentService::class)->initiateOnlineCheckout($order, app(PaymentProvider::class));

        $response = $this->get($result['checkout_url']);

        $response->assertOk();
        $response->assertSee(number_format($order->balance_due, 2));
    }
}
