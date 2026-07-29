<?php

namespace Tests\Feature\Phase3;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderStatusException;
use App\Services\OrderStatusService;
use App\Services\OrderStatusTransitions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowed_transition_map_permits_checked_in_to_tagged(): void
    {
        $this->assertTrue(OrderStatusTransitions::isAllowed(OrderStatus::CHECKED_IN, OrderStatus::TAGGED));
    }

    public function test_transition_map_forbids_skipping_ahead(): void
    {
        $this->assertFalse(OrderStatusTransitions::isAllowed(OrderStatus::CHECKED_IN, OrderStatus::COMPLETED));
    }

    public function test_terminal_statuses_have_no_further_transitions(): void
    {
        $this->assertEmpty(OrderStatusTransitions::allowedNext(OrderStatus::COMPLETED));
        $this->assertEmpty(OrderStatusTransitions::allowedNext(OrderStatus::CANCELLED));
    }

    public function test_service_transition_updates_status_and_creates_history(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CHECKED_IN]);

        $updated = app(OrderStatusService::class)->transition($order, OrderStatus::TAGGED, $user);

        $this->assertEquals(OrderStatus::TAGGED, $updated->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'checked_in',
            'to_status' => 'tagged',
            'is_override' => false,
        ]);
    }

    public function test_service_rejects_invalid_transition_without_override(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CHECKED_IN]);

        $this->expectException(OrderStatusException::class);

        app(OrderStatusService::class)->transition($order, OrderStatus::COMPLETED, $user);
    }

    public function test_override_requires_reason(): void
    {
        $location = Location::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::OWNER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CHECKED_IN]);

        $this->expectException(OrderStatusException::class);

        app(OrderStatusService::class)->transition($order, OrderStatus::COMPLETED, $owner, null, true);
    }

    public function test_override_with_reason_succeeds_and_logs_audit(): void
    {
        $location = Location::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::OWNER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CHECKED_IN]);

        app(OrderStatusService::class)->transition($order, OrderStatus::COMPLETED, $owner, 'Customer picked up early, staff error', true);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'completed',
            'is_override' => true,
            'reason' => 'Customer picked up early, staff error',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Order',
            'model_id' => $order->id,
            'action' => 'override_status',
        ]);
    }

    public function test_http_status_update_by_cashier_within_allowed_map(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CHECKED_IN]);

        $response = $this->actingAs($user)->patch(route('orders.status', $order), [
            'status' => OrderStatus::TAGGED->value,
        ]);

        $response->assertRedirect();
        $this->assertEquals(OrderStatus::TAGGED, $order->fresh()->status);
    }

    public function test_http_status_update_rejects_illegal_transition(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CHECKED_IN]);

        $response = $this->actingAs($user)->patch(route('orders.status', $order), [
            'status' => OrderStatus::COMPLETED->value,
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals(OrderStatus::CHECKED_IN, $order->fresh()->status);
    }

    public function test_non_owner_cannot_perform_override(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CHECKED_IN]);

        $response = $this->actingAs($manager)->patch(route('orders.status', $order), [
            'status' => OrderStatus::COMPLETED->value,
            'override' => '1',
            'confirm_override' => '1',
            'reason' => 'Testing',
        ]);

        $response->assertForbidden();
    }

    public function test_owner_override_requires_confirmation_checkbox(): void
    {
        $location = Location::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::OWNER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CHECKED_IN]);

        $response = $this->actingAs($owner)->patch(route('orders.status', $order), [
            'status' => OrderStatus::COMPLETED->value,
            'override' => '1',
            'reason' => 'Testing override',
        ]);

        $response->assertSessionHasErrors('confirm_override');
    }

    public function test_accountant_cannot_update_order_status(): void
    {
        $location = Location::factory()->create();
        $accountant = User::factory()->create(['role' => UserRole::ACCOUNTANT, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CHECKED_IN]);

        $response = $this->actingAs($accountant)->patch(route('orders.status', $order), [
            'status' => OrderStatus::TAGGED->value,
        ]);

        $response->assertForbidden();
    }

    public function test_staff_can_be_assigned_to_order(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $staff = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($manager)->patch(route('orders.assign', $order), [
            'assigned_user_id' => $staff->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($staff->id, $order->fresh()->assigned_user_id);
    }

    public function test_assignment_creates_audit_log(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $staff = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $this->actingAs($manager)->patch(route('orders.assign', $order), [
            'assigned_user_id' => $staff->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Order',
            'model_id' => $order->id,
            'action' => 'updated',
        ]);
    }
}
