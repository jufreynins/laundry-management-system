<?php

namespace Database\Factories;

use App\Enums\IntakeChannel;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'location_id' => Location::factory(),
            'intake_channel' => IntakeChannel::WALK_IN,
            'status' => OrderStatus::CHECKED_IN,
            'intake_at' => now(),
            'promised_at' => now()->addDays(2),
            'rush' => false,
            'created_by' => User::factory(),
            'subtotal' => 20.00,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'tip_amount' => 0,
            'total' => 20.00,
            'amount_paid' => 0,
            'balance_due' => 20.00,
        ];
    }
}
