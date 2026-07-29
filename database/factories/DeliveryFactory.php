<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'location_id' => \App\Models\Location::factory(),
            'type' => DeliveryType::DELIVERY,
            'status' => DeliveryStatus::SCHEDULED,
            'scheduled_at' => now()->addDay(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip' => $this->faker->postcode(),
            'fee' => 5.00,
            'created_by' => User::factory(),
        ];
    }
}
