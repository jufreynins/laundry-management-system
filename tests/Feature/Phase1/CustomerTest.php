<?php

namespace Tests\Feature\Phase1;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_customer_list(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        Customer::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertOk();
    }

    public function test_manager_customer_list_excludes_other_locations(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location1->id]);
        $ownCustomer = Customer::factory()->create(['location_id' => $location1->id, 'name' => 'Own Location Customer']);
        $otherCustomer = Customer::factory()->create(['location_id' => $location2->id, 'name' => 'Other Location Customer']);

        $response = $this->actingAs($manager)->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee($ownCustomer->customer_number);
        $response->assertDontSee($otherCustomer->customer_number);
    }

    public function test_guest_cannot_view_customer_list(): void
    {
        $response = $this->get(route('customers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_customer_creation_requires_valid_data(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'location_id' => $location->id,
            'name' => '',
            'phone' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_customer_can_be_created_with_valid_data(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'location_id' => $location->id,
            'name' => 'Jane Doe',
            'phone' => '5551234567',
            'email' => 'jane@example.com',
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Jane Doe',
            'phone' => '5551234567',
        ]);

        $customer = Customer::where('name', 'Jane Doe')->first();
        $response->assertRedirect(route('customers.show', $customer));
    }

    public function test_customer_number_is_generated_automatically(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);

        $this->assertNotNull($customer->customer_number);
        $this->assertStringStartsWith('CUS-', $customer->customer_number);
    }

    public function test_non_admin_cannot_set_arbitrary_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id]);

        $this->actingAs($user)->post(route('customers.store'), [
            'location_id' => $location2->id,
            'name' => 'Jane Doe',
            'phone' => '5551234567',
        ]);

        $customer = Customer::where('name', 'Jane Doe')->first();
        $this->assertEquals($location1->id, $customer->location_id);
    }

    public function test_user_cannot_view_customer_from_different_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id]);
        $customer = Customer::factory()->create(['location_id' => $location2->id]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertForbidden();
    }

    public function test_owner_can_view_customer_from_any_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::OWNER, 'location_id' => $location1->id]);
        $customer = Customer::factory()->create(['location_id' => $location2->id]);

        $response = $this->actingAs($owner)->get(route('customers.show', $customer));

        $response->assertOk();
    }

    public function test_accountant_cannot_create_customer(): void
    {
        $location = Location::factory()->create();
        $accountant = User::factory()->create(['role' => UserRole::ACCOUNTANT, 'location_id' => $location->id]);

        $response = $this->actingAs($accountant)->post(route('customers.store'), [
            'location_id' => $location->id,
            'name' => 'Jane Doe',
            'phone' => '5551234567',
        ]);

        $response->assertForbidden();
    }

    public function test_customer_can_be_updated(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $customer = Customer::factory()->create(['location_id' => $location->id, 'name' => 'Old Name']);

        $response = $this->actingAs($user)->patch(route('customers.update', $customer), [
            'name' => 'New Name',
            'phone' => $customer->phone,
        ]);

        $response->assertRedirect(route('customers.show', $customer));
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'New Name']);
    }

    public function test_customer_update_creates_audit_log(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $customer = Customer::factory()->create(['location_id' => $location->id]);

        $this->actingAs($user)->patch(route('customers.update', $customer), [
            'name' => 'Updated Name',
            'phone' => $customer->phone,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Customer',
            'model_id' => $customer->id,
            'action' => 'updated',
        ]);
    }

    public function test_customer_search_by_phone(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $customer = Customer::factory()->create(['location_id' => $location->id, 'phone' => '5559998888']);

        $response = $this->actingAs($user)->get(route('customers.index', ['q' => '5559998888']));

        $response->assertOk();
        $response->assertSee($customer->customer_number);
    }

    public function test_duplicate_customer_warning_shown(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        Customer::factory()->create(['location_id' => $location->id, 'phone' => '5551112222', 'name' => 'John Smith']);

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'location_id' => $location->id,
            'name' => 'John Smith',
            'phone' => '5551112222',
        ]);

        $response->assertSessionHas('status');
        $this->assertStringContainsString('duplicate', session('status'));
    }
}
