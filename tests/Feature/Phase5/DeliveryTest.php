<?php

namespace Tests\Feature\Phase5;

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\DeliveryZone;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Services\DeliveryException;
use App\Services\DeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_schedule_delivery_with_zone_fee(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);
        $zone = DeliveryZone::factory()->create(['location_id' => $location->id, 'fee' => 7.50]);

        $delivery = app(DeliveryService::class)->schedule($order, [
            'type' => DeliveryType::DELIVERY->value,
            'scheduled_at' => now()->addDay(),
            'delivery_zone_id' => $zone->id,
            'address' => '123 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62701',
        ], $manager);

        $this->assertEquals('7.50', (string) $delivery->fee);
        $this->assertEquals(DeliveryStatus::SCHEDULED, $delivery->status);
    }

    public function test_scheduling_creates_audit_log(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $delivery = app(DeliveryService::class)->schedule($order, [
            'type' => DeliveryType::PICKUP->value,
            'scheduled_at' => now()->addDay(),
            'address' => '1 A St', 'city' => 'X', 'state' => 'IL', 'zip' => '60000',
        ], $manager);

        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Delivery',
            'model_id' => $delivery->id,
            'action' => 'created',
        ]);
    }

    public function test_http_schedule_delivery_via_cashier(): void
    {
        $location = Location::factory()->create();
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($cashier)->post(route('deliveries.store', $order), [
            'type' => DeliveryType::DELIVERY->value,
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'address' => '123 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62701',
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('deliveries', ['order_id' => $order->id]);
    }

    public function test_driver_can_update_own_assigned_delivery_status(): void
    {
        $location = Location::factory()->create();
        $driver = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);
        $delivery = Delivery::factory()->create(['location_id' => $location->id, 'driver_id' => $driver->id]);

        $response = $this->actingAs($driver)->patch(route('deliveries.status', $delivery), [
            'status' => DeliveryStatus::EN_ROUTE->value,
        ]);

        $response->assertRedirect();
        $this->assertEquals(DeliveryStatus::EN_ROUTE, $delivery->fresh()->status);
    }

    public function test_driver_cannot_update_delivery_assigned_to_another_driver(): void
    {
        $location = Location::factory()->create();
        $driver1 = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);
        $driver2 = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);
        $delivery = Delivery::factory()->create(['location_id' => $location->id, 'driver_id' => $driver1->id]);

        $response = $this->actingAs($driver2)->patch(route('deliveries.status', $delivery), [
            'status' => DeliveryStatus::EN_ROUTE->value,
        ]);

        $response->assertForbidden();
    }

    public function test_completing_delivery_requires_proof_notes(): void
    {
        $location = Location::factory()->create();
        $driver = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);
        $delivery = Delivery::factory()->create(['location_id' => $location->id, 'driver_id' => $driver->id]);

        $this->expectException(DeliveryException::class);
        app(DeliveryService::class)->updateStatus($delivery, DeliveryStatus::COMPLETED, null, $driver);
    }

    public function test_completing_delivery_with_proof_notes_succeeds(): void
    {
        $location = Location::factory()->create();
        $driver = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);
        $delivery = Delivery::factory()->create(['location_id' => $location->id, 'driver_id' => $driver->id]);

        $updated = app(DeliveryService::class)->updateStatus($delivery, DeliveryStatus::COMPLETED, 'Left at front door per instructions', $driver);

        $this->assertEquals(DeliveryStatus::COMPLETED, $updated->status);
        $this->assertNotNull($updated->completed_at);
        $this->assertEquals('Left at front door per instructions', $updated->proof_notes);
    }

    public function test_cannot_update_status_of_terminal_delivery(): void
    {
        $location = Location::factory()->create();
        $driver = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);
        $delivery = Delivery::factory()->create([
            'location_id' => $location->id,
            'driver_id' => $driver->id,
            'status' => DeliveryStatus::COMPLETED,
        ]);

        $this->expectException(DeliveryException::class);
        app(DeliveryService::class)->updateStatus($delivery, DeliveryStatus::EN_ROUTE, null, $driver);
    }

    public function test_manager_can_assign_driver(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $driver = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);
        $delivery = Delivery::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($manager)->patch(route('deliveries.driver', $delivery), [
            'driver_id' => $driver->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($driver->id, $delivery->fresh()->driver_id);
    }

    public function test_cashier_cannot_assign_driver(): void
    {
        $location = Location::factory()->create();
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $driver = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);
        $delivery = Delivery::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($cashier)->patch(route('deliveries.driver', $delivery), [
            'driver_id' => $driver->id,
        ]);

        $response->assertForbidden();
    }

    public function test_driver_dashboard_only_shows_own_active_deliveries(): void
    {
        $location = Location::factory()->create();
        $driver = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);
        $otherDriver = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);

        $mine = Delivery::factory()->create(['location_id' => $location->id, 'driver_id' => $driver->id]);
        Delivery::factory()->create(['location_id' => $location->id, 'driver_id' => $otherDriver->id]);
        Delivery::factory()->create(['location_id' => $location->id, 'driver_id' => $driver->id, 'status' => DeliveryStatus::COMPLETED]);

        $response = $this->actingAs($driver)->get(route('driver.index'));

        $response->assertOk();
        $response->assertSee($mine->order->order_number);
    }

    public function test_delivery_zone_creation_by_owner(): void
    {
        $location = Location::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::OWNER, 'location_id' => $location->id]);

        $response = $this->actingAs($owner)->post(route('delivery-zones.store'), [
            'location_id' => $location->id,
            'name' => 'North Zone',
            'fee' => 6.00,
        ]);

        $response->assertRedirect(route('delivery-zones.index'));
        $this->assertDatabaseHas('delivery_zones', ['name' => 'North Zone', 'fee' => 6.00]);
    }

    public function test_cashier_cannot_create_delivery_zone(): void
    {
        $location = Location::factory()->create();
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);

        $response = $this->actingAs($cashier)->post(route('delivery-zones.store'), [
            'location_id' => $location->id,
            'name' => 'North Zone',
            'fee' => 6.00,
        ]);

        $response->assertForbidden();
    }
}
