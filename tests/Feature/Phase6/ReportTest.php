<?php

namespace Tests\Feature\Phase6;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_reports_page(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);

        $response = $this->actingAs($owner)->get(route('reports.index'));

        $response->assertOk();
    }

    public function test_accountant_can_view_reports_page(): void
    {
        $accountant = User::factory()->create(['role' => UserRole::ACCOUNTANT]);

        $response = $this->actingAs($accountant)->get(route('reports.index'));

        $response->assertOk();
    }

    public function test_cashier_cannot_view_reports_page(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);

        $response = $this->actingAs($cashier)->get(route('reports.index'));

        $response->assertForbidden();
    }

    public function test_sales_by_service_aggregates_line_totals(): void
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create(['name' => 'Wash and Fold']);
        $order = Order::factory()->create(['location_id' => $location->id, 'intake_at' => now()]);
        OrderItem::factory()->create(['order_id' => $order->id, 'service_id' => $service->id, 'description' => 'Wash and Fold', 'line_total' => 30]);
        OrderItem::factory()->create(['order_id' => $order->id, 'service_id' => $service->id, 'description' => 'Wash and Fold', 'line_total' => 20]);

        $result = app(ReportService::class)->salesByService($location->id, now()->startOfMonth(), now()->endOfDay());

        $this->assertEquals(50, (float) $result[0]['total']);
        $this->assertEquals(2, $result[0]['line_count']);
    }

    public function test_sales_by_service_excludes_orders_outside_date_range(): void
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $oldOrder = Order::factory()->create(['location_id' => $location->id, 'intake_at' => now()->subMonths(2)]);
        OrderItem::factory()->create(['order_id' => $oldOrder->id, 'service_id' => $service->id, 'line_total' => 99]);

        $result = app(ReportService::class)->salesByService($location->id, now()->startOfMonth(), now()->endOfDay());

        $this->assertEmpty($result);
    }

    public function test_tax_summary_sums_order_tax(): void
    {
        $location = Location::factory()->create();
        Order::factory()->create(['location_id' => $location->id, 'intake_at' => now(), 'tax_amount' => 5.50]);
        Order::factory()->create(['location_id' => $location->id, 'intake_at' => now(), 'tax_amount' => 4.50]);

        $tax = app(ReportService::class)->taxSummary($location->id, now()->startOfMonth(), now()->endOfDay());

        $this->assertEquals('10.00', $tax);
    }

    public function test_payment_summary_groups_by_method_and_excludes_voided(): void
    {
        $location = Location::factory()->create();
        Payment::factory()->create(['location_id' => $location->id, 'method' => PaymentMethod::CASH, 'status' => PaymentStatus::COMPLETED, 'amount' => 40]);
        Payment::factory()->create(['location_id' => $location->id, 'method' => PaymentMethod::CASH, 'status' => PaymentStatus::COMPLETED, 'amount' => 10]);
        Payment::factory()->create(['location_id' => $location->id, 'method' => PaymentMethod::CASH, 'status' => PaymentStatus::VOIDED, 'amount' => 1000]);

        $result = app(ReportService::class)->paymentSummaryByMethod($location->id, now()->startOfMonth(), now()->endOfDay());

        $this->assertCount(1, $result);
        $this->assertEquals(50, (float) $result[0]['total']);
    }

    public function test_sales_by_location_only_shown_to_admin(): void
    {
        $location = Location::factory()->create();
        Order::factory()->create(['location_id' => $location->id, 'intake_at' => now(), 'total' => 25]);
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);

        $ownerResponse = $this->actingAs($owner)->get(route('reports.index'));
        $managerResponse = $this->actingAs($manager)->get(route('reports.index'));

        $ownerResponse->assertOk()->assertSee('Sales by Location');
        $managerResponse->assertOk()->assertDontSee('Sales by Location');
    }
}
