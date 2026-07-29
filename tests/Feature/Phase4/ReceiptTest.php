<?php

namespace Tests\Feature\Phase4;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_receipt(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($user)->get(route('orders.receipt', $order));

        $response->assertOk();
        $response->assertSee($order->order_number);
    }

    public function test_authorized_user_can_view_claim_ticket(): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location->id]);
        $order = Order::factory()->create(['location_id' => $location->id]);

        $response = $this->actingAs($user)->get(route('orders.claim-ticket', $order));

        $response->assertOk();
        $response->assertSee($order->order_number);
    }

    public function test_user_from_different_location_cannot_view_receipt(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CASHIER, 'location_id' => $location1->id]);
        $order = Order::factory()->create(['location_id' => $location2->id]);

        $response = $this->actingAs($user)->get(route('orders.receipt', $order));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_receipt(): void
    {
        $order = Order::factory()->create();

        $response = $this->get(route('orders.receipt', $order));

        $response->assertRedirect(route('login'));
    }
}
