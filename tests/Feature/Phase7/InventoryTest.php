<?php

namespace Tests\Feature\Phase7;

use App\Enums\InventoryTransactionType;
use App\Enums\UserRole;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\User;
use App\Services\InventoryException;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_receiving_stock_increases_quantity(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $item = InventoryItem::factory()->create(['location_id' => $location->id, 'current_quantity' => 10]);

        app(InventoryService::class)->recordTransaction($item, InventoryTransactionType::RECEIVED, '5.00', null, $user);

        $this->assertEquals('15.00', (string) $item->fresh()->current_quantity);
    }

    public function test_using_stock_decreases_quantity(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF, 'location_id' => $location->id]);
        $item = InventoryItem::factory()->create(['location_id' => $location->id, 'current_quantity' => 10]);

        app(InventoryService::class)->recordTransaction($item, InventoryTransactionType::USED, '3.00', 'Daily wash', $user);

        $this->assertEquals('7.00', (string) $item->fresh()->current_quantity);
    }

    public function test_usage_cannot_exceed_current_stock(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF, 'location_id' => $location->id]);
        $item = InventoryItem::factory()->create(['location_id' => $location->id, 'current_quantity' => 5]);

        $this->expectException(InventoryException::class);
        app(InventoryService::class)->recordTransaction($item, InventoryTransactionType::USED, '10.00', null, $user);
    }

    public function test_negative_or_zero_quantity_rejected_for_received(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $item = InventoryItem::factory()->create(['location_id' => $location->id]);

        $this->expectException(InventoryException::class);
        app(InventoryService::class)->recordTransaction($item, InventoryTransactionType::RECEIVED, '0.00', null, $user);
    }

    public function test_adjustment_can_be_negative(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $item = InventoryItem::factory()->create(['location_id' => $location->id, 'current_quantity' => 10]);

        app(InventoryService::class)->recordTransaction($item, InventoryTransactionType::ADJUSTMENT, '-2.00', 'Count correction', $user);

        $this->assertEquals('8.00', (string) $item->fresh()->current_quantity);
    }

    public function test_transaction_creates_immutable_ledger_entry(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $item = InventoryItem::factory()->create(['location_id' => $location->id, 'current_quantity' => 10]);

        $transaction = app(InventoryService::class)->recordTransaction($item, InventoryTransactionType::RECEIVED, '5.00', null, $user);

        $this->assertDatabaseHas('inventory_transactions', [
            'id' => $transaction->id,
            'quantity' => 5.00,
            'quantity_after' => 15.00,
        ]);
    }

    public function test_transaction_creates_audit_log(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $item = InventoryItem::factory()->create(['location_id' => $location->id]);

        app(InventoryService::class)->recordTransaction($item, InventoryTransactionType::RECEIVED, '5.00', null, $user);

        $this->assertDatabaseHas('audit_logs', [
            'model' => 'InventoryItem',
            'model_id' => $item->id,
            'action' => 'updated',
        ]);
    }

    public function test_cashier_cannot_create_inventory_item(): void
    {
        $location = Location::factory()->create();
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);

        $response = $this->actingAs($cashier)->post(route('inventory.store'), [
            'location_id' => $location->id,
            'name' => 'Detergent',
            'unit' => 'bottle',
            'current_quantity' => 10,
            'reorder_threshold' => 2,
        ]);

        $response->assertForbidden();
    }

    public function test_laundry_staff_can_record_usage_but_not_create_items(): void
    {
        $location = Location::factory()->create();
        $staff = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF, 'location_id' => $location->id]);
        $item = InventoryItem::factory()->create(['location_id' => $location->id, 'current_quantity' => 10]);

        $response = $this->actingAs($staff)->post(route('inventory.transactions.store', $item), [
            'type' => InventoryTransactionType::USED->value,
            'quantity' => 2,
        ]);
        $response->assertRedirect();

        $createResponse = $this->actingAs($staff)->get(route('inventory.create'));
        $createResponse->assertForbidden();
    }

    public function test_driver_cannot_record_inventory_transaction(): void
    {
        $location = Location::factory()->create();
        $driver = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location->id]);
        $item = InventoryItem::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($driver)->post(route('inventory.transactions.store', $item), [
            'type' => InventoryTransactionType::USED->value,
            'quantity' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_below_reorder_threshold_detection(): void
    {
        $item = InventoryItem::factory()->create(['current_quantity' => 2, 'reorder_threshold' => 5]);

        $this->assertTrue($item->isBelowReorderThreshold());
    }

    public function test_user_from_different_location_cannot_record_transaction(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location1->id]);
        $item = InventoryItem::factory()->create(['location_id' => $location2->id]);

        $response = $this->actingAs($manager)->post(route('inventory.transactions.store', $item), [
            'type' => InventoryTransactionType::RECEIVED->value,
            'quantity' => 5,
        ]);

        $response->assertForbidden();
    }
}
