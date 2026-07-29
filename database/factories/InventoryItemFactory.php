<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => 'Detergent',
            'unit' => 'bottle',
            'current_quantity' => 20,
            'reorder_threshold' => 5,
            'active' => true,
        ];
    }
}
