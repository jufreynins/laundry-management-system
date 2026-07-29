<?php

namespace Tests\Feature\Phase10;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\Location;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    // --- Admin user create/edit forms don't offer choices a non-Owner can't submit ---

    public function test_manager_create_user_form_does_not_offer_owner_or_manager_roles(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);

        $response = $this->actingAs($manager)->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertDontSee('value="owner"', false);
        $response->assertDontSee('value="manager"', false);
        $response->assertDontSee('All Locations (Owner only)');
    }

    public function test_owner_create_user_form_offers_all_roles_and_locations_option(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        Location::factory()->create();

        $response = $this->actingAs($owner)->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertSee('value="owner"', false);
        $response->assertSee('value="manager"', false);
        $response->assertSee('All Locations (Owner only)');
    }

    public function test_manager_edit_user_form_does_not_offer_owner_or_manager_roles(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);

        $response = $this->actingAs($manager)->get(route('admin.users.edit', $cashier));

        $response->assertOk();
        $response->assertDontSee('value="owner"', false);
        $response->assertDontSee('value="manager"', false);
    }

    // --- Cross-location FK validation ---

    public function test_cannot_assign_order_to_staff_member_from_a_different_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location1->id]);
        $otherLocationStaff = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF, 'location_id' => $location2->id]);
        $order = Order::factory()->create(['location_id' => $location1->id]);

        $response = $this->actingAs($manager)->patch(route('orders.assign', $order), [
            'assigned_user_id' => $otherLocationStaff->id,
        ]);

        $response->assertSessionHasErrors('assigned_user_id');
    }

    public function test_cannot_assign_delivery_to_driver_from_a_different_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location1->id]);
        $otherLocationDriver = User::factory()->create(['role' => UserRole::DRIVER, 'location_id' => $location2->id]);
        $delivery = Delivery::factory()->create(['location_id' => $location1->id]);

        $response = $this->actingAs($manager)->patch(route('deliveries.driver', $delivery), [
            'driver_id' => $otherLocationDriver->id,
        ]);

        $response->assertSessionHasErrors('driver_id');
    }

    public function test_manager_cannot_create_supplier_for_a_different_location(): void
    {
        $ownLocation = Location::factory()->create();
        $otherLocation = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $ownLocation->id]);

        $this->actingAs($manager)->post(route('suppliers.store'), [
            'location_id' => $otherLocation->id,
            'name' => 'Sneaky Supplier',
        ]);

        $supplier = Supplier::where('name', 'Sneaky Supplier')->first();
        $this->assertEquals($ownLocation->id, $supplier->location_id);
    }

    // --- Admin locations list restricted to management roles ---

    public function test_cashier_cannot_view_admin_locations_list(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);

        $response = $this->actingAs($cashier)->get(route('admin.locations.index'));

        $response->assertForbidden();
    }

    public function test_manager_admin_locations_list_excludes_other_locations(): void
    {
        $location1 = Location::factory()->create(['name' => 'My Own Store']);
        $location2 = Location::factory()->create(['name' => 'Some Other Store']);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location1->id]);

        $response = $this->actingAs($manager)->get(route('admin.locations.index'));

        $response->assertOk();
        $response->assertSee('My Own Store');
        $response->assertDontSee('Some Other Store');
    }

    public function test_owner_sees_all_locations_in_admin_list(): void
    {
        Location::factory()->create(['name' => 'Store A']);
        Location::factory()->create(['name' => 'Store B']);
        $owner = User::factory()->create(['role' => UserRole::OWNER]);

        $response = $this->actingAs($owner)->get(route('admin.locations.index'));

        $response->assertOk();
        $response->assertSee('Store A');
        $response->assertSee('Store B');
    }

    // --- Privilege escalation: Manager cannot create/promote to Owner/Manager ---

    public function test_manager_cannot_create_owner_account(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);

        $response = $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'a-very-long-password-123',
            'password_confirmation' => 'a-very-long-password-123',
            'role' => UserRole::OWNER->value,
            'location_id' => $location->id,
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_manager_cannot_create_manager_account(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);

        $response = $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Peer Manager',
            'email' => 'peer@example.com',
            'password' => 'a-very-long-password-123',
            'password_confirmation' => 'a-very-long-password-123',
            'role' => UserRole::MANAGER->value,
            'location_id' => $location->id,
        ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_owner_can_create_manager_account(): void
    {
        $location = Location::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::OWNER]);

        $response = $this->actingAs($owner)->post(route('admin.users.store'), [
            'name' => 'New Manager',
            'email' => 'newmanager@example.com',
            'password' => 'a-very-long-password-123',
            'password_confirmation' => 'a-very-long-password-123',
            'role' => UserRole::MANAGER->value,
            'location_id' => $location->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'newmanager@example.com', 'role' => 'manager']);
    }

    public function test_manager_cannot_create_user_at_different_location(): void
    {
        $ownLocation = Location::factory()->create();
        $otherLocation = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $ownLocation->id]);

        $response = $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Cross Location Hire',
            'email' => 'crosslocation@example.com',
            'password' => 'a-very-long-password-123',
            'password_confirmation' => 'a-very-long-password-123',
            'role' => UserRole::CASHIER->value,
            'location_id' => $otherLocation->id,
        ]);

        $response->assertSessionHasErrors('location_id');
        $this->assertDatabaseMissing('users', ['email' => 'crosslocation@example.com']);
    }

    public function test_manager_can_create_cashier_at_own_location(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);

        $response = $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'New Cashier',
            'email' => 'newcashier@example.com',
            'password' => 'a-very-long-password-123',
            'password_confirmation' => 'a-very-long-password-123',
            'role' => UserRole::CASHIER->value,
            'location_id' => $location->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'newcashier@example.com']);
    }

    public function test_manager_cannot_promote_existing_user_to_owner(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);

        $response = $this->actingAs($manager)->patch(route('admin.users.update', $cashier), [
            'name' => $cashier->name,
            'email' => $cashier->email,
            'role' => UserRole::OWNER->value,
            'location_id' => $location->id,
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertEquals(UserRole::CASHIER, $cashier->fresh()->role);
    }

    public function test_manager_editing_own_profile_without_changing_role_succeeds(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id, 'name' => 'Old Name']);

        $response = $this->actingAs($manager)->patch(route('admin.users.update', $manager), [
            'name' => 'Updated Name',
            'email' => $manager->email,
            'role' => UserRole::MANAGER->value,
            'location_id' => $location->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals('Updated Name', $manager->fresh()->name);
    }

    public function test_manager_cannot_move_user_to_different_location(): void
    {
        $ownLocation = Location::factory()->create();
        $otherLocation = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $ownLocation->id]);
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $ownLocation->id]);

        $response = $this->actingAs($manager)->patch(route('admin.users.update', $cashier), [
            'name' => $cashier->name,
            'email' => $cashier->email,
            'role' => UserRole::CASHIER->value,
            'location_id' => $otherLocation->id,
        ]);

        $response->assertSessionHasErrors('location_id');
        $this->assertEquals($ownLocation->id, $cashier->fresh()->location_id);
    }

    // --- Admin user list is location-scoped ---

    public function test_manager_user_list_excludes_other_locations(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location1->id]);
        $ownStaff = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id, 'name' => 'Own Staff Member']);
        $otherStaff = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location2->id, 'name' => 'Other Staff Member']);

        $response = $this->actingAs($manager)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Own Staff Member');
        $response->assertDontSee('Other Staff Member');
    }

    // --- Audit log access restricted to management roles ---

    public function test_cashier_cannot_view_audit_logs(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);

        $response = $this->actingAs($cashier)->get(route('audit-logs.index'));

        $response->assertForbidden();
    }

    public function test_driver_cannot_view_audit_logs(): void
    {
        $driver = User::factory()->create(['role' => UserRole::DRIVER]);

        $response = $this->actingAs($driver)->get(route('audit-logs.index'));

        $response->assertForbidden();
    }

    public function test_manager_can_view_audit_logs(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);

        $response = $this->actingAs($manager)->get(route('audit-logs.index'));

        $response->assertOk();
    }

    public function test_accountant_can_view_audit_logs(): void
    {
        $accountant = User::factory()->create(['role' => UserRole::ACCOUNTANT]);

        $response = $this->actingAs($accountant)->get(route('audit-logs.index'));

        $response->assertOk();
    }

    // --- Business settings IDOR: Manager cannot view another location's settings ---

    public function test_manager_cannot_view_another_locations_business_settings(): void
    {
        $ownLocation = Location::factory()->create();
        $otherLocation = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $ownLocation->id]);

        $response = $this->actingAs($manager)->get(route('admin.settings.index', ['location_id' => $otherLocation->id]));

        $response->assertForbidden();
    }

    public function test_manager_can_view_own_locations_business_settings(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);

        $response = $this->actingAs($manager)->get(route('admin.settings.index', ['location_id' => $location->id]));

        $response->assertOk();
    }

    public function test_owner_can_view_any_locations_business_settings(): void
    {
        $location = Location::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::OWNER]);

        $response = $this->actingAs($owner)->get(route('admin.settings.index', ['location_id' => $location->id]));

        $response->assertOk();
    }
}
