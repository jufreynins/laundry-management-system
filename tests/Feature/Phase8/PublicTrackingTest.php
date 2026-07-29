<?php

namespace Tests\Feature\Phase8;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_token_shows_order_safe_details(): void
    {
        $order = Order::factory()->create();

        $response = $this->get(route('public.tracking.show', $order->tracking_token));

        $response->assertOk();
        $response->assertSee($order->order_number);
    }

    public function test_invalid_token_returns_404(): void
    {
        $response = $this->get(route('public.tracking.show', 'nonexistent-token-xyz'));

        $response->assertNotFound();
    }

    public function test_tracking_token_is_generated_automatically(): void
    {
        $order = Order::factory()->create();

        $this->assertNotNull($order->tracking_token);
        $this->assertEquals(40, strlen($order->tracking_token));
    }

    public function test_tracking_page_does_not_expose_customer_pii(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create([
            'location_id' => $location->id,
            'email' => 'secretcustomer@example.com',
            'phone' => '5559998877',
            'address' => '123 Secret Lane',
        ]);
        $order = Order::factory()->create(['location_id' => $location->id, 'customer_id' => $customer->id]);

        $response = $this->get(route('public.tracking.show', $order->tracking_token));

        $response->assertOk();
        $response->assertDontSee('secretcustomer@example.com');
        $response->assertDontSee('5559998877');
        $response->assertDontSee('123 Secret Lane');
        $response->assertDontSee($customer->name);
    }

    public function test_tracking_page_does_not_expose_internal_notes(): void
    {
        $order = Order::factory()->create(['internal_notes' => 'Confidential staff note about difficult customer']);

        $response = $this->get(route('public.tracking.show', $order->tracking_token));

        $response->assertOk();
        $response->assertDontSee('Confidential staff note');
    }

    public function test_tracking_page_shows_customer_safe_status_label(): void
    {
        $order = Order::factory()->create(['status' => \App\Enums\OrderStatus::WASHING]);

        $response = $this->get(route('public.tracking.show', $order->tracking_token));

        $response->assertOk();
        $response->assertSee('In Progress');
        $response->assertDontSee('Washing');
    }

    public function test_tracking_is_rate_limited(): void
    {
        $order = Order::factory()->create();

        for ($i = 0; $i < 20; $i++) {
            $this->get(route('public.tracking.show', $order->tracking_token));
        }

        $response = $this->get(route('public.tracking.show', $order->tracking_token));

        $response->assertStatus(429);
    }
}
