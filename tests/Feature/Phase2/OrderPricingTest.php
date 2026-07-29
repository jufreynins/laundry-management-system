<?php

namespace Tests\Feature\Phase2;

use App\Enums\IntakeChannel;
use App\Enums\PricingType;
use App\Enums\ServiceCategory;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderPricingException;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPricingTest extends TestCase
{
    use RefreshDatabase;

    private function baseData(Location $location, Customer $customer, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'intake_channel' => IntakeChannel::WALK_IN->value,
            'promised_at' => null,
            'rush' => false,
            'items' => [],
        ], $overrides);
    }

    public function test_per_pound_pricing_calculates_correctly(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);
        $service = Service::factory()->create([
            'pricing_type' => PricingType::PER_POUND,
            'base_price' => 1.75,
            'minimum_charge' => null,
            'taxable' => false,
        ]);

        $order = app(OrderService::class)->createOrder(
            $this->baseData($location, $customer, ['items' => [['service_id' => $service->id, 'quantity' => 10]]]),
            $user->id
        );

        $this->assertEquals('17.50', (string) $order->subtotal);
        $this->assertEquals('17.50', (string) $order->total);
    }

    public function test_minimum_charge_applied_when_below_threshold(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);
        $service = Service::factory()->create([
            'pricing_type' => PricingType::PER_POUND,
            'base_price' => 1.75,
            'minimum_charge' => 15.00,
            'taxable' => false,
        ]);

        // 2 lbs * 1.75 = 3.50, below the 15.00 minimum
        $order = app(OrderService::class)->createOrder(
            $this->baseData($location, $customer, ['items' => [['service_id' => $service->id, 'quantity' => 2]]]),
            $user->id
        );

        $this->assertEquals('15.00', (string) $order->subtotal);
    }

    public function test_flat_fee_pricing_ignores_quantity(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);
        $service = Service::factory()->create([
            'pricing_type' => PricingType::FLAT_FEE,
            'base_price' => 25.00,
            'minimum_charge' => null,
            'taxable' => false,
        ]);

        $order = app(OrderService::class)->createOrder(
            $this->baseData($location, $customer, ['items' => [['service_id' => $service->id, 'quantity' => 5]]]),
            $user->id
        );

        $this->assertEquals('25.00', (string) $order->subtotal);
    }

    public function test_client_supplied_price_is_ignored_server_calculates_authoritative_price(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);
        $service = Service::factory()->create([
            'pricing_type' => PricingType::PER_ITEM,
            'base_price' => 5.00,
            'minimum_charge' => null,
            'taxable' => false,
        ]);

        // Attempt to smuggle a manipulated unit_price / line_total — the service
        // only reads service_id + quantity, so these extra keys must be ignored.
        $order = app(OrderService::class)->createOrder(
            $this->baseData($location, $customer, ['items' => [[
                'service_id' => $service->id,
                'quantity' => 3,
                'unit_price' => 0.01,
                'line_total' => 0.03,
            ]]]),
            $user->id
        );

        $this->assertEquals('15.00', (string) $order->subtotal);
        $this->assertEquals('5.00', (string) $order->items->first()->unit_price);
    }

    public function test_tax_applied_only_to_taxable_items(): void
    {
        $location = Location::factory()->create();
        $location->setSetting('sales_tax_enabled', '1');
        $location->setSetting('tax_rate', '10');
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);

        $taxableService = Service::factory()->create([
            'pricing_type' => PricingType::FLAT_FEE, 'base_price' => 100, 'taxable' => true, 'minimum_charge' => null,
        ]);
        $nonTaxableService = Service::factory()->create([
            'pricing_type' => PricingType::FLAT_FEE, 'base_price' => 50, 'taxable' => false, 'minimum_charge' => null,
        ]);

        $order = app(OrderService::class)->createOrder(
            $this->baseData($location, $customer, ['items' => [
                ['service_id' => $taxableService->id, 'quantity' => 1],
                ['service_id' => $nonTaxableService->id, 'quantity' => 1],
            ]]),
            $user->id
        );

        // Only the $100 taxable line is taxed at 10% = $10.00
        $this->assertEquals('10.00', (string) $order->tax_amount);
        $this->assertEquals('150.00', (string) $order->subtotal);
    }

    public function test_no_tax_when_sales_tax_disabled(): void
    {
        $location = Location::factory()->create();
        $location->setSetting('sales_tax_enabled', '0');
        $location->setSetting('tax_rate', '10');
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);
        $service = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 100, 'taxable' => true, 'minimum_charge' => null]);

        $order = app(OrderService::class)->createOrder(
            $this->baseData($location, $customer, ['items' => [['service_id' => $service->id, 'quantity' => 1]]]),
            $user->id
        );

        $this->assertEquals('0.00', (string) $order->tax_amount);
    }

    public function test_discount_cannot_exceed_subtotal(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);
        $service = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 10, 'minimum_charge' => null]);

        $this->expectException(OrderPricingException::class);

        app(OrderService::class)->createOrder(
            $this->baseData($location, $customer, [
                'items' => [['service_id' => $service->id, 'quantity' => 1]],
                'discount_amount' => 50,
            ]),
            $user->id
        );
    }

    public function test_order_requires_at_least_one_item(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);

        $this->expectException(OrderPricingException::class);

        app(OrderService::class)->createOrder($this->baseData($location, $customer, ['items' => []]), $user->id);
    }

    public function test_invalid_service_id_rolls_back_entire_order(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);
        $validService = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 10, 'minimum_charge' => null]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        try {
            app(OrderService::class)->createOrder(
                $this->baseData($location, $customer, ['items' => [
                    ['service_id' => $validService->id, 'quantity' => 1],
                    ['service_id' => 999999, 'quantity' => 1],
                ]]),
                $user->id
            );
        } finally {
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('order_items', 0);
        }
    }

    public function test_order_number_format(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);
        $service = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 10, 'minimum_charge' => null]);

        $order = app(OrderService::class)->createOrder(
            $this->baseData($location, $customer, ['items' => [['service_id' => $service->id, 'quantity' => 1]]]),
            $user->id
        );

        $this->assertMatchesRegularExpression('/^LND-\d{4}-\d{6}$/', $order->order_number);
    }

    public function test_order_numbers_increment_sequentially(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['location_id' => $location->id]);
        $service = Service::factory()->create(['pricing_type' => PricingType::FLAT_FEE, 'base_price' => 10, 'minimum_charge' => null]);

        $data = $this->baseData($location, $customer, ['items' => [['service_id' => $service->id, 'quantity' => 1]]]);

        $order1 = app(OrderService::class)->createOrder($data, $user->id);
        $order2 = app(OrderService::class)->createOrder($data, $user->id);

        $num1 = (int) substr($order1->order_number, -6);
        $num2 = (int) substr($order2->order_number, -6);

        $this->assertEquals($num1 + 1, $num2);
    }
}
