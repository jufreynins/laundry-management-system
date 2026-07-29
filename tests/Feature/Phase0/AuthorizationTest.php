<?php

namespace Tests\Feature\Phase0;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_users(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $user = User::factory()->create();

        $this->assertTrue($owner->can('viewAny', User::class));
    }

    public function test_staff_cannot_view_users(): void
    {
        $staff = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF]);
        $user = User::factory()->create();

        $this->assertFalse($staff->can('viewAny', User::class));
    }

    public function test_owner_can_create_users(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);

        $this->assertTrue($owner->can('create', User::class));
    }

    public function test_manager_can_create_users(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);

        $this->assertTrue($manager->can('create', User::class));
    }

    public function test_cashier_cannot_create_users(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);

        $this->assertFalse($cashier->can('create', User::class));
    }

    public function test_admin_can_view_location(): void
    {
        $location = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);

        $this->assertTrue($manager->can('view', $location));
    }

    public function test_staff_cannot_edit_location(): void
    {
        $location = Location::factory()->create();
        $staff = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF, 'location_id' => $location->id]);

        $this->assertFalse($staff->can('update', $location));
    }

    public function test_admin_from_different_location_cannot_access(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location1->id]);

        $this->assertFalse($manager->can('view', $location2));
    }

    public function test_owner_can_access_any_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $owner = User::factory()->create(['role' => UserRole::OWNER, 'location_id' => $location1->id]);

        $this->assertTrue($owner->can('view', $location2));
    }
}
