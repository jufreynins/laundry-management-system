<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
class DeliveryZoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => 'Downtown Zone',
            'fee' => 5.00,
            'active' => true,
        ];
    }
}
