<?php

namespace Database\Factories;

use App\Enums\PricingType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'service_id' => Service::factory(),
            'description' => 'Wash and Fold',
            'pricing_type' => PricingType::PER_POUND,
            'quantity' => 10,
            'unit_price' => 1.75,
            'line_total' => 17.50,
            'taxable' => true,
        ];
    }
}
