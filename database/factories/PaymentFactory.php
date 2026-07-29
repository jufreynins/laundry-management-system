<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'location_id' => \App\Models\Location::factory(),
            'method' => PaymentMethod::CASH,
            'status' => PaymentStatus::COMPLETED,
            'amount' => 20.00,
            'recorded_by' => User::factory(),
        ];
    }
}
