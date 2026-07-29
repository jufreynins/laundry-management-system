<?php

namespace Tests\Feature\Phase7;

use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_record_expense(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $category = ExpenseCategory::factory()->create();

        $response = $this->actingAs($manager)->post(route('expenses.store'), [
            'location_id' => $location->id,
            'expense_category_id' => $category->id,
            'amount' => 45.50,
            'description' => 'Detergent restock',
            'expense_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', ['description' => 'Detergent restock', 'amount' => 45.50]);
    }

    public function test_cashier_cannot_record_expense(): void
    {
        $location = Location::factory()->create();
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $category = ExpenseCategory::factory()->create();

        $response = $this->actingAs($cashier)->post(route('expenses.store'), [
            'location_id' => $location->id,
            'expense_category_id' => $category->id,
            'amount' => 45.50,
            'description' => 'Detergent restock',
            'expense_date' => now()->toDateString(),
        ]);

        $response->assertForbidden();
    }

    public function test_expense_requires_positive_amount(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $category = ExpenseCategory::factory()->create();

        $response = $this->actingAs($manager)->post(route('expenses.store'), [
            'location_id' => $location->id,
            'expense_category_id' => $category->id,
            'amount' => 0,
            'description' => 'Invalid expense',
            'expense_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_expense_creates_audit_log(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $category = ExpenseCategory::factory()->create();

        $this->actingAs($manager)->post(route('expenses.store'), [
            'location_id' => $location->id,
            'expense_category_id' => $category->id,
            'amount' => 20,
            'description' => 'Test expense',
            'expense_date' => now()->toDateString(),
        ]);

        $expense = Expense::first();
        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Expense',
            'model_id' => $expense->id,
            'action' => 'created',
        ]);
    }

    public function test_manager_expense_list_excludes_other_locations(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location1->id]);
        $ownExpense = Expense::factory()->create(['location_id' => $location1->id, 'description' => 'Own Expense']);
        $otherExpense = Expense::factory()->create(['location_id' => $location2->id, 'description' => 'Other Expense']);

        $response = $this->actingAs($manager)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('Own Expense');
        $response->assertDontSee('Other Expense');
    }

    public function test_accountant_can_view_but_not_create_expense(): void
    {
        $location = Location::factory()->create();
        $accountant = User::factory()->create(['role' => UserRole::ACCOUNTANT, 'location_id' => $location->id]);
        $category = ExpenseCategory::factory()->create();

        $viewResponse = $this->actingAs($accountant)->get(route('expenses.index'));
        $viewResponse->assertOk();

        $createResponse = $this->actingAs($accountant)->post(route('expenses.store'), [
            'location_id' => $location->id,
            'expense_category_id' => $category->id,
            'amount' => 20,
            'description' => 'Test',
            'expense_date' => now()->toDateString(),
        ]);
        $createResponse->assertForbidden();
    }

    public function test_owner_can_create_expense_category(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);

        $response = $this->actingAs($owner)->post(route('expense-categories.store'), [
            'name' => 'Utilities',
        ]);

        $response->assertRedirect(route('expense-categories.index'));
        $this->assertDatabaseHas('expense_categories', ['name' => 'Utilities']);
    }

    public function test_manager_cannot_create_expense_category(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);

        $response = $this->actingAs($manager)->post(route('expense-categories.store'), [
            'name' => 'Utilities',
        ]);

        $response->assertForbidden();
    }
}
