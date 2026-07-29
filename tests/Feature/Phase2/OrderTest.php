<?php

namespace Tests\Feature\Phase2;

use App\Enums\IntakeChannel;
use App\Enums\PricingType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_orders(): void
    {
        $response = $this->get(route('orders.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_create_form_redirects_with_message_when_no_location_exists(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);

        $response = $this->actingAs($owner)->get(route('orders.create'));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('status');
        $this->assertStringContainsString('Location', session('status'));
    }

    public function test_create_form_redirects_when_no_customer_exists(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);

        $response = $this->actingAs($user)->get(route('orders.create'));

        $response->assertRedirect(route('customers.create'));
        $this->assertStringContainsString('Customer', session('status'));
    }

    public function test_create_form_redirects_when_no_service_exists(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        Customer::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($user)->get(route('orders.create'));

        $response->assertRedirect(route('services.index'));
        $this->assertStringContainsString('Service', session('status'));
    }

    public function test_manager_order_list_excludes_other_locations(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location1->id]);
        $ownOrder = Order::factory()->create(['location_id' => $location1->id]);
        $otherOrder = Order::factory()->create(['location_id' => $location2->id]);

        $response = $this->actingAs($manager)->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee($ownOrder->order_number);
        $response->assertDontSee($otherOrder->order_number);
    }

    public function test_cashier_can_create_order_via_http(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $service = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 20, 'minimum_charge' => null]);

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'intake_channel' => IntakeChannel::WALK_IN->value,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);

        $order = Order::first();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertNotNull($order);
        $this->assertEquals('20.00', (string) $order->total);
    }

    public function test_accountant_cannot_create_order(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['role' => UserRole::ACCOUNTANT, 'location_id' => $location->id]);
        $service = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 20, 'minimum_charge' => null]);

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'intake_channel' => IntakeChannel::WALK_IN->value,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);

        $response->assertForbidden();
    }

    public function test_order_validation_requires_items(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'intake_channel' => IntakeChannel::WALK_IN->value,
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_cannot_create_order_for_customer_from_different_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location2->id]);
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id]);
        $service = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 20, 'minimum_charge' => null]);

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'location_id' => $location1->id,
            'intake_channel' => IntakeChannel::WALK_IN->value,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);

        $response->assertSessionHasErrors('customer_id');
    }

    public function test_user_cannot_view_order_from_different_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id]);
        $order = Order::factory()->create(['location_id' => $location2->id]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertForbidden();
    }

    public function test_owner_can_view_order_from_any_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::OWNER, 'location_id' => $location1->id]);
        $order = Order::factory()->create(['location_id' => $location2->id]);

        $response = $this->actingAs($owner)->get(route('orders.show', $order));

        $response->assertOk();
    }

    public function test_order_creates_initial_status_history(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $service = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 20, 'minimum_charge' => null]);

        $this->actingAs($user)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'intake_channel' => IntakeChannel::WALK_IN->value,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);

        $order = Order::first();
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'checked_in',
            'from_status' => null,
        ]);
    }

    public function test_order_creates_audit_log(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $service = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 20, 'minimum_charge' => null]);

        $this->actingAs($user)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'intake_channel' => IntakeChannel::WALK_IN->value,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);

        $order = Order::first();
        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Order',
            'model_id' => $order->id,
            'action' => 'created',
        ]);
    }

    public function test_cross_location_order_attempt_is_blocked_by_validation(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location1->id]);
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id]);
        $service = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 20, 'minimum_charge' => null]);

        // Attempt to submit a different location_id than the customer actually belongs to
        $this->actingAs($user)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'location_id' => $location2->id,
            'intake_channel' => IntakeChannel::WALK_IN->value,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);

        $this->assertDatabaseCount('orders', 0);
    }
}
