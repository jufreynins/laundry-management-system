<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'expense_category_id' => ExpenseCategory::factory(),
            'amount' => 50.00,
            'description' => 'Supplies purchase',
            'expense_date' => now()->toDateString(),
            'recorded_by' => User::factory(),
        ];
    }
}
