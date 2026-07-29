<?php

namespace Tests\Feature\Phase0;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_account(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('test@example.com', $user->email);
    }

    public function test_user_password_is_hashed(): void
    {
        $plainPassword = 'password123';
        $user = User::factory()->create([
            'password' => bcrypt($plainPassword),
        ]);

        $this->assertTrue(password_verify($plainPassword, $user->password));
    }

    public function test_user_has_default_staff_role(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(UserRole::LAUNDRY_STAFF, $user->role);
    }

    public function test_user_can_be_assigned_role(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MANAGER,
        ]);

        $this->assertEquals(UserRole::MANAGER, $user->role);
    }

    public function test_user_can_be_assigned_location(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create([
            'location_id' => $location->id,
        ]);

        $this->assertEquals($location->id, $user->location_id);
        $this->assertEquals($location->id, $user->location->id);
    }

    public function test_user_can_be_active_or_inactive(): void
    {
        $activeUser = User::factory()->create(['active' => true]);
        $inactiveUser = User::factory()->create(['active' => false]);

        $this->assertTrue($activeUser->active);
        $this->assertFalse($inactiveUser->active);
    }

    public function test_user_has_role_check_method(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $staff = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF]);

        $this->assertTrue($manager->hasRole(UserRole::MANAGER));
        $this->assertFalse($staff->hasRole(UserRole::MANAGER));
    }

    public function test_user_is_admin_check(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $staff = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF]);

        $this->assertTrue($owner->isAdmin());
        $this->assertTrue($manager->isAdmin());
        $this->assertFalse($staff->isAdmin());
    }
}
