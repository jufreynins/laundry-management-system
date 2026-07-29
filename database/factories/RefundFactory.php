<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'order_id' => Order::factory(),
            'amount' => 5.00,
            'reason' => 'Customer complaint',
            'processed_by' => User::factory(),
            'created_at' => now(),
        ];
    }
}
