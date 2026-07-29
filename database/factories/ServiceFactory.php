<?php

namespace Database\Factories;

use App\Enums\PricingType;
use App\Enums\ServiceCategory;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Wash and Fold',
            'category' => ServiceCategory::WASH_FOLD,
            'pricing_type' => PricingType::PER_POUND,
            'base_price' => 1.75,
            'minimum_charge' => 15.00,
            'taxable' => true,
            'rush_eligible' => true,
            'estimated_duration_minutes' => 1440,
            'active' => true,
        ];
    }
}
