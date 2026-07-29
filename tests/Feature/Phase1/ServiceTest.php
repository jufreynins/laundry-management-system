<?php

namespace Tests\Feature\Phase1;

use App\Enums\PricingType;
use App\Enums\ServiceCategory;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_authenticated_user_can_view_services(): void
    {
        $user = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF]);
        Service::factory()->create();

        $response = $this->actingAs($user)->get(route('services.index'));

        $response->assertOk();
    }

    public function test_staff_cannot_create_service(): void
    {
        $user = User::factory()->create(['role' => UserRole::LAUNDRY_STAFF]);

        $response = $this->actingAs($user)->post(route('services.store'), [
            'name' => 'Wash and Fold',
            'category' => ServiceCategory::WASH_FOLD->value,
            'pricing_type' => PricingType::PER_POUND->value,
            'base_price' => 1.75,
        ]);

        $response->assertForbidden();
    }

    public function test_manager_can_create_service(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);

        $response = $this->actingAs($user)->post(route('services.store'), [
            'name' => 'Wash and Fold',
            'category' => ServiceCategory::WASH_FOLD->value,
            'pricing_type' => PricingType::PER_POUND->value,
            'base_price' => 1.75,
            'taxable' => true,
        ]);

        $this->assertDatabaseHas('services', ['name' => 'Wash and Fold']);
        $service = Service::where('name', 'Wash and Fold')->first();
        $response->assertRedirect(route('services.show', $service));
    }

    public function test_service_price_cannot_be_negative(): void
    {
        $user = User::factory()->create(['role' => UserRole::OWNER]);

        $response = $this->actingAs($user)->post(route('services.store'), [
            'name' => 'Wash and Fold',
            'category' => ServiceCategory::WASH_FOLD->value,
            'pricing_type' => PricingType::PER_POUND->value,
            'base_price' => -5,
        ]);

        $response->assertSessionHasErrors('base_price');
    }

    public function test_service_requires_valid_category_enum(): void
    {
        $user = User::factory()->create(['role' => UserRole::OWNER]);

        $response = $this->actingAs($user)->post(route('services.store'), [
            'name' => 'Wash and Fold',
            'category' => 'not_a_real_category',
            'pricing_type' => PricingType::PER_POUND->value,
            'base_price' => 1.75,
        ]);

        $response->assertSessionHasErrors('category');
    }

    public function test_location_specific_price_overrides_base_price(): void
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create(['base_price' => 1.75]);
        ServicePrice::create([
            'service_id' => $service->id,
            'location_id' => $location->id,
            'price' => 2.25,
            'active' => true,
        ]);

        $this->assertEquals('2.25', $service->priceForLocation($location->id));
    }

    public function test_service_without_override_uses_base_price(): void
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create(['base_price' => 1.75]);

        $this->assertEquals('1.75', $service->priceForLocation($location->id));
    }

    public function test_service_can_be_updated_by_owner(): void
    {
        $user = User::factory()->create(['role' => UserRole::OWNER]);
        $service = Service::factory()->create(['base_price' => 1.75]);

        $response = $this->actingAs($user)->patch(route('services.update', $service), [
            'name' => $service->name,
            'category' => $service->category->value,
            'pricing_type' => $service->pricing_type->value,
            'base_price' => 2.00,
        ]);

        $response->assertRedirect(route('services.show', $service));
        $this->assertDatabaseHas('services', ['id' => $service->id, 'base_price' => 2.00]);
    }

    public function test_service_update_creates_audit_log(): void
    {
        $user = User::factory()->create(['role' => UserRole::OWNER]);
        $service = Service::factory()->create();

        $this->actingAs($user)->patch(route('services.update', $service), [
            'name' => $service->name,
            'category' => $service->category->value,
            'pricing_type' => $service->pricing_type->value,
            'base_price' => 3.00,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Service',
            'model_id' => $service->id,
            'action' => 'updated',
        ]);
    }
}
