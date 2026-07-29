<?php

namespace Tests\Feature\Phase0;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\BusinessSettings;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_model_can_be_created(): void
    {
        $location = Location::factory()->create([
            'name' => 'Downtown Store',
            'city' => 'New York',
            'state' => 'NY',
        ]);

        $this->assertNotNull($location->id);
        $this->assertEquals('Downtown Store', $location->name);
        $this->assertEquals('NY', $location->state);
    }

    public function test_location_has_default_timezone(): void
    {
        $location = Location::factory()->create();

        $this->assertEquals('America/New_York', $location->timezone);
    }

    public function test_location_can_set_and_get_setting(): void
    {
        $location = Location::factory()->create();
        $location->setSetting('tax_rate', '0.08');

        $this->assertEquals('0.08', $location->setting('tax_rate'));
    }

    public function test_business_settings_stored_with_location(): void
    {
        $location = Location::factory()->create();
        $location->setSetting('store_name', 'Main Laundry');

        $setting = BusinessSettings::where('location_id', $location->id)
            ->where('key', 'store_name')
            ->first();

        $this->assertNotNull($setting);
        $this->assertEquals('Main Laundry', $setting->value);
    }

    public function test_audit_log_can_be_created(): void
    {
        $user = User::factory()->create();
        $location = Location::factory()->create();

        $auditLog = AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::LOGIN->value,
            'model' => 'User',
            'model_id' => $user->id,
            'location_id' => $location->id,
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertNotNull($auditLog->id);
        $this->assertEquals('login', $auditLog->action);
    }

    public function test_audit_log_can_store_old_and_new_values(): void
    {
        $user = User::factory()->create();

        $auditLog = AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::UPDATED->value,
            'model' => 'User',
            'model_id' => $user->id,
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
        ]);

        $this->assertEquals(['name' => 'Old Name'], $auditLog->old_values);
        $this->assertEquals(['name' => 'New Name'], $auditLog->new_values);
    }

    public function test_user_can_have_audit_logs(): void
    {
        $user = User::factory()->create();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::LOGIN->value,
            'model' => 'User',
            'model_id' => $user->id,
        ]);

        $this->assertCount(1, $user->auditLogs);
    }

    public function test_location_has_users(): void
    {
        $location = Location::factory()->create();
        User::factory()->create(['location_id' => $location->id]);
        User::factory()->create(['location_id' => $location->id]);

        $this->assertCount(2, $location->users);
    }

    public function test_location_has_business_settings(): void
    {
        $location = Location::factory()->create();
        $location->setSetting('tax_rate', '0.08');
        $location->setSetting('store_name', 'Main');

        $this->assertCount(2, $location->businessSettings);
    }

    public function test_user_can_check_location_access(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $staff = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF, 'location_id' => $location1->id]);
        $owner = User::factory()->create(['role' => UserRole::OWNER, 'location_id' => $location1->id]);

        $this->assertTrue($staff->canAccessLocation($location1));
        $this->assertFalse($staff->canAccessLocation($location2));
        $this->assertTrue($owner->canAccessLocation($location2));
    }
}
