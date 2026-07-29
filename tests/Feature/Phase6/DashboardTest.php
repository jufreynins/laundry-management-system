<?php

namespace Tests\Feature\Phase6;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_orders_today_counts_only_todays_orders(): void
    {
        $location = Location::factory()->create();
        Order::factory()->create(['location_id' => $location->id, 'intake_at' => now()]);
        Order::factory()->create(['location_id' => $location->id, 'intake_at' => now()->subDays(2)]);

        $count = app(ReportService::class)->ordersToday($location->id);

        $this->assertEquals(1, $count);
    }

    public function test_amount_due_excludes_completed_and_cancelled_orders(): void
    {
        $location = Location::factory()->create();
        Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CHECKED_IN, 'balance_due' => 50]);
        Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::COMPLETED, 'balance_due' => 0]);
        Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::CANCELLED, 'balance_due' => 20]);

        $amountDue = app(ReportService::class)->totalAmountDue($location->id);

        $this->assertEquals('50.00', $amountDue);
    }

    public function test_ready_for_pickup_count(): void
    {
        $location = Location::factory()->create();
        Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::READY_FOR_PICKUP]);
        Order::factory()->create(['location_id' => $location->id, 'status' => OrderStatus::WASHING]);

        $count = app(ReportService::class)->readyForPickupCount($location->id);

        $this->assertEquals(1, $count);
    }

    public function test_overdue_orders_excludes_completed_and_future_promised(): void
    {
        $location = Location::factory()->create();
        $overdue = Order::factory()->create([
            'location_id' => $location->id,
            'status' => OrderStatus::WASHING,
            'promised_at' => now()->subDay(),
        ]);
        Order::factory()->create([
            'location_id' => $location->id,
            'status' => OrderStatus::WASHING,
            'promised_at' => now()->addDay(),
        ]);
        Order::factory()->create([
            'location_id' => $location->id,
            'status' => OrderStatus::COMPLETED,
            'promised_at' => now()->subDay(),
        ]);

        $overdueOrders = app(ReportService::class)->overdueOrders($location->id);

        $this->assertCount(1, $overdueOrders);
        $this->assertEquals($overdue->id, $overdueOrders->first()->id);
    }

    public function test_non_admin_orders_today_only_reflects_own_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        Order::factory()->create(['location_id' => $location1->id, 'intake_at' => now()]);
        Order::factory()->create(['location_id' => $location2->id, 'intake_at' => now()]);

        $count = app(ReportService::class)->ordersToday($location1->id);

        $this->assertEquals(1, $count);
    }
}
