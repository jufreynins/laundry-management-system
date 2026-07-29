<?php

namespace Tests\Feature\Phase4;

use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentException;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
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

    public function test_full_cash_payment_zeroes_balance_due(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['location_id' => $location->id]);
        $order = $this->orderWithBalance($location);

        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $user);

        $order->refresh();
        $this->assertEquals('0.00', (string) $order->balance_due);
        $this->assertEquals('100.00', (string) $order->amount_paid);
        $this->assertNotNull($payment->payment_reference);
        $this->assertStringStartsWith('PAY-', $payment->payment_reference);
    }

    public function test_partial_payment_reduces_balance_due(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['location_id' => $location->id]);
        $order = $this->orderWithBalance($location);

        app(PaymentService::class)->recordPayment($order, '40.00', PaymentMethod::CASH->value, null, $user);

        $order->refresh();
        $this->assertEquals('60.00', (string) $order->balance_due);
        $this->assertEquals('40.00', (string) $order->amount_paid);
    }

    public function test_overpayment_is_prevented(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['location_id' => $location->id]);
        $order = $this->orderWithBalance($location, '50.00');

        $this->expectException(PaymentException::class);

        app(PaymentService::class)->recordPayment($order, '75.00', PaymentMethod::CASH->value, null, $user);
    }

    public function test_zero_or_negative_payment_rejected(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['location_id' => $location->id]);
        $order = $this->orderWithBalance($location);

        $this->expectException(PaymentException::class);

        app(PaymentService::class)->recordPayment($order, '0.00', PaymentMethod::CASH->value, null, $user);
    }

    public function test_duplicate_idempotency_key_rejected(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['location_id' => $location->id]);
        $order = $this->orderWithBalance($location);

        app(PaymentService::class)->recordPayment($order, '40.00', PaymentMethod::CASH->value, null, $user, 'dup-key-123');

        $this->expectException(PaymentException::class);
        app(PaymentService::class)->recordPayment($order, '40.00', PaymentMethod::CASH->value, null, $user, 'dup-key-123');
    }

    public function test_payment_creates_audit_log(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['location_id' => $location->id]);
        $order = $this->orderWithBalance($location);

        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $user);

        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Payment',
            'model_id' => $payment->id,
            'action' => 'payment_recorded',
        ]);
    }

    public function test_http_payment_recording_via_cashier(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);

        $response = $this->actingAs($user)->post(route('payments.store', $order), [
            'amount' => '100.00',
            'method' => PaymentMethod::CASH->value,
            'idempotency_key' => 'key-abc-1',
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount' => 100.00]);
    }

    public function test_accountant_cannot_record_payment(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::ACCOUNTANT, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);

        $response = $this->actingAs($user)->post(route('payments.store', $order), [
            'amount' => '50.00',
            'method' => PaymentMethod::CASH->value,
            'idempotency_key' => 'key-abc-2',
        ]);

        $response->assertForbidden();
    }

    public function test_http_duplicate_submission_with_same_idempotency_key_fails(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location, '200.00');

        $this->actingAs($user)->post(route('payments.store', $order), [
            'amount' => '50.00',
            'method' => PaymentMethod::CASH->value,
            'idempotency_key' => 'double-submit-key',
        ]);

        $response = $this->actingAs($user)->post(route('payments.store', $order), [
            'amount' => '50.00',
            'method' => PaymentMethod::CASH->value,
            'idempotency_key' => 'double-submit-key',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_void_reverses_order_balance(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location, '100.00');
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $manager);

        app(PaymentService::class)->voidPayment($payment, 'Recorded in error', $manager);

        $order->refresh();
        $this->assertEquals('100.00', (string) $order->balance_due);
        $this->assertEquals('0.00', (string) $order->amount_paid);
    }

    public function test_cashier_cannot_void_payment(): void
    {
        $location = Location::factory()->create();
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $cashier);

        $response = $this->actingAs($cashier)->patch(route('payments.void', $payment), [
            'reason' => 'testing',
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_void_already_voided_payment(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $manager);
        app(PaymentService::class)->voidPayment($payment, 'first void', $manager);

        $this->expectException(PaymentException::class);
        app(PaymentService::class)->voidPayment($payment->fresh(), 'second void', $manager);
    }

    public function test_void_requires_reason(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $manager);

        $this->expectException(PaymentException::class);
        app(PaymentService::class)->voidPayment($payment, '', $manager);
    }

    public function test_user_from_different_location_cannot_pay_order(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id]);
        $order = $this->orderWithBalance($location2);

        $response = $this->actingAs($user)->post(route('payments.store', $order), [
            'amount' => '50.00',
            'method' => PaymentMethod::CASH->value,
            'idempotency_key' => 'cross-loc-key',
        ]);

        $response->assertForbidden();
    }
}
