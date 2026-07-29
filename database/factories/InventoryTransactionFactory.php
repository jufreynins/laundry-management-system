<?php

namespace Database\Factories;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransaction>
 */
class InventoryTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'type' => InventoryTransactionType::RECEIVED,
            'quantity' => 10,
            'quantity_after' => 30,
            'recorded_by' => User::factory(),
            'created_at' => now(),
        ];
    }
}
