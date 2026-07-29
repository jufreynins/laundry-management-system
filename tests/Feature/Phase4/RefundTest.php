<?php

namespace Tests\Feature\Phase4;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Services\PaymentException;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundTest extends TestCase
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

    public function test_full_refund_marks_payment_refunded_and_restores_balance(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $manager);

        $refund = app(PaymentService::class)->refundPayment($payment, '100.00', 'Customer dissatisfied', $manager);

        $order->refresh();
        $payment->refresh();
        $this->assertEquals(PaymentStatus::REFUNDED, $payment->status);
        $this->assertEquals('100.00', (string) $order->balance_due);
        $this->assertEquals('0.00', (string) $order->amount_paid);
        $this->assertStringStartsWith('REF-', $refund->refund_reference);
    }

    public function test_partial_refund_marks_payment_partially_refunded(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $manager);

        app(PaymentService::class)->refundPayment($payment, '30.00', 'Partial dissatisfaction', $manager);

        $payment->refresh();
        $order->refresh();
        $this->assertEquals(PaymentStatus::PARTIALLY_REFUNDED, $payment->status);
        $this->assertEquals('70.00', (string) $order->amount_paid);
        $this->assertEquals('30.00', (string) $order->balance_due);
    }

    public function test_refund_cannot_exceed_refundable_amount(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $manager);

        $this->expectException(PaymentException::class);
        app(PaymentService::class)->refundPayment($payment, '150.00', 'Too much', $manager);
    }

    public function test_cannot_refund_beyond_remaining_after_partial_refund(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $manager);
        app(PaymentService::class)->refundPayment($payment, '60.00', 'First refund', $manager);

        $this->expectException(PaymentException::class);
        app(PaymentService::class)->refundPayment($payment->fresh(), '50.00', 'Second refund too much', $manager);
    }

    public function test_refund_requires_reason(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $manager);

        $this->expectException(PaymentException::class);
        app(PaymentService::class)->refundPayment($payment, '50.00', '', $manager);
    }

    public function test_refund_creates_audit_log(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $manager);

        $refund = app(PaymentService::class)->refundPayment($payment, '25.00', 'Reason here', $manager);

        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Refund',
            'model_id' => $refund->id,
            'action' => 'refund_issued',
        ]);
    }

    public function test_cashier_cannot_issue_refund_via_http(): void
    {
        $location = Location::factory()->create();
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $cashier);

        $response = $this->actingAs($cashier)->post(route('payments.refund', $payment), [
            'amount' => '20.00',
            'reason' => 'testing',
        ]);

        $response->assertForbidden();
    }

    public function test_manager_can_issue_refund_via_http(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = $this->orderWithBalance($location);
        $payment = app(PaymentService::class)->recordPayment($order, '100.00', PaymentMethod::CASH->value, null, $manager);

        $response = $this->actingAs($manager)->post(route('payments.refund', $payment), [
            'amount' => '20.00',
            'reason' => 'Customer request',
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('refunds', ['payment_id' => $payment->id, 'amount' => 20.00]);
    }
}
