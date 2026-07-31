<?php

namespace Database\Seeders;

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use App\Enums\InventoryTransactionType;
use App\Enums\IntakeChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PricingType;
use App\Enums\ServiceCategory;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\Order;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DeliveryService;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\OrderStatusService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Populates realistic demo data across every module for a single existing
 * location, using the app's own service classes (OrderService, PaymentService,
 * DeliveryService, InventoryService, OrderStatusService) so pricing, numbering,
 * status-history, and audit-log side effects all happen exactly as they would
 * for a real user — this is not raw factory spam.
 *
 * Run standalone: php artisan db:seed --class=DemoDataSeeder
 * (Do NOT chain through DatabaseSeeder, which uses WithoutModelEvents and
 * would blank out order/customer/payment auto-generated numbers.)
 */
class DemoDataSeeder extends Seeder
{
    private OrderService $orderService;
    private OrderStatusService $orderStatusService;
    private PaymentService $paymentService;
    private DeliveryService $deliveryService;
    private InventoryService $inventoryService;

    private Location $location;
    private User $owner;
    private User $manager;
    private User $cashier;
    private User $staff;
    private User $driver;
    private User $accountant;

    public function run(): void
    {
        $this->orderService = new OrderService();
        $this->orderStatusService = new OrderStatusService();
        $this->paymentService = new PaymentService();
        $this->deliveryService = new DeliveryService();
        $this->inventoryService = new InventoryService();

        $this->location = Location::where('name', 'Main Store')->first() ?? Location::firstOrFail();
        $this->resolveUsers();

        $this->fixLocationAddress();
        $this->enableSalesTax();

        // Remove an obvious throwaway "Test" customer left over from manual testing.
        Customer::where('email', 'test@gmail.com')->where('name', 'Test')->delete();

        $categories = $this->seedExpenseCategories();
        $suppliers = $this->seedSuppliers();
        $services = $this->seedServices();
        $zones = $this->seedDeliveryZones();
        $this->seedInventory($suppliers);
        $customers = $this->seedCustomers();
        $this->seedOrders($customers, $services, $zones);
        $this->seedExpenses($categories, $suppliers);

        $this->command?->info('Demo data seeded.');
    }

    private function resolveUsers(): void
    {
        $byRole = fn (UserRole $role) => User::where('role', $role->value)->first();

        $this->owner = $byRole(UserRole::OWNER) ?? User::firstOrFail();
        $this->manager = $byRole(UserRole::MANAGER) ?? $this->owner;
        $this->cashier = $byRole(UserRole::CASHIER) ?? $this->owner;
        $this->staff = $byRole(UserRole::LAUNDRY_STAFF) ?? $this->owner;
        $this->driver = $byRole(UserRole::DRIVER) ?? $this->owner;
        $this->accountant = $byRole(UserRole::ACCOUNTANT) ?? $this->owner;
    }

    private function fixLocationAddress(): void
    {
        if ($this->location->address === 'Please update this address' || $this->location->city === 'Please update') {
            $this->location->update([
                'address' => '148 W 24th St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10011',
                'phone' => '212-555-0142',
            ]);
        }
    }

    private function enableSalesTax(): void
    {
        $this->location->setSetting('sales_tax_enabled', '1');
        $this->location->setSetting('tax_rate', '8.875');
    }

    private function seedExpenseCategories(): array
    {
        $names = ['Utilities', 'Detergent & Supplies', 'Equipment Maintenance', 'Rent', 'Payroll', 'Marketing & Advertising'];
        $out = [];
        foreach ($names as $name) {
            $out[$name] = ExpenseCategory::firstOrCreate(['name' => $name], ['active' => true]);
        }
        return $out;
    }

    private function seedSuppliers(): array
    {
        $rows = [
            ['name' => 'CleanChem Supply Co.', 'contact_name' => 'Maria Santos', 'phone' => '212-555-0110', 'email' => 'orders@cleanchemsupply.com', 'address' => '221 Industrial Pkwy, Long Island City, NY 11101'],
            ['name' => 'ProLaundry Equipment Parts', 'contact_name' => 'James Wu', 'phone' => '718-555-0166', 'email' => 'parts@prolaundryequip.com', 'address' => '55 Machinery Rd, Queens, NY 11378'],
            ['name' => 'Uniform & Linen Wholesale', 'contact_name' => 'Rachel Kim', 'phone' => '917-555-0133', 'email' => 'sales@uniformlinenwholesale.com', 'address' => '890 Textile Ave, Brooklyn, NY 11205'],
            ['name' => 'PackRight Packaging Solutions', 'contact_name' => 'Tom Alvarez', 'phone' => '646-555-0188', 'email' => 'support@packrightsolutions.com', 'address' => '14 Carton St, Newark, NJ 07102'],
        ];

        $out = [];
        foreach ($rows as $row) {
            $out[$row['name']] = Supplier::firstOrCreate(
                ['name' => $row['name'], 'location_id' => $this->location->id],
                $row + ['location_id' => $this->location->id, 'notes' => null, 'active' => true]
            );
        }
        return $out;
    }

    private function seedServices(): array
    {
        $rows = [
            ['name' => 'Wash and Fold', 'category' => ServiceCategory::WASH_FOLD, 'pricing_type' => PricingType::PER_POUND, 'base_price' => '1.75', 'minimum_charge' => '15.00', 'taxable' => true, 'rush_eligible' => true, 'estimated_duration_minutes' => 1440],
            ['name' => 'Wash and Press', 'category' => ServiceCategory::WASH_PRESS, 'pricing_type' => PricingType::PER_POUND, 'base_price' => '2.25', 'minimum_charge' => '18.00', 'taxable' => true, 'rush_eligible' => true, 'estimated_duration_minutes' => 1440],
            ['name' => 'Dry Cleaning - Shirt', 'category' => ServiceCategory::DRY_CLEANING, 'pricing_type' => PricingType::PER_ITEM, 'base_price' => '4.50', 'minimum_charge' => '10.00', 'taxable' => true, 'rush_eligible' => true, 'estimated_duration_minutes' => 2880],
            ['name' => 'Dry Cleaning - Suit (2pc)', 'category' => ServiceCategory::DRY_CLEANING, 'pricing_type' => PricingType::PER_ITEM, 'base_price' => '14.00', 'minimum_charge' => '14.00', 'taxable' => true, 'rush_eligible' => true, 'estimated_duration_minutes' => 2880],
            ['name' => 'Dry Cleaning - Dress', 'category' => ServiceCategory::DRY_CLEANING, 'pricing_type' => PricingType::PER_ITEM, 'base_price' => '11.00', 'minimum_charge' => '11.00', 'taxable' => true, 'rush_eligible' => true, 'estimated_duration_minutes' => 2880],
            ['name' => 'Alterations & Repairs', 'category' => ServiceCategory::ALTERATIONS, 'pricing_type' => PricingType::HOURLY, 'base_price' => '35.00', 'minimum_charge' => '20.00', 'taxable' => true, 'rush_eligible' => false, 'estimated_duration_minutes' => 4320],
            ['name' => 'Shoe Cleaning', 'category' => ServiceCategory::SHOE_CLEANING, 'pricing_type' => PricingType::FLAT_FEE, 'base_price' => '25.00', 'minimum_charge' => null, 'taxable' => true, 'rush_eligible' => true, 'estimated_duration_minutes' => 1440],
            ['name' => 'Wedding Dress Preservation', 'category' => ServiceCategory::DRY_CLEANING, 'pricing_type' => PricingType::CUSTOM_QUOTE, 'base_price' => '150.00', 'minimum_charge' => null, 'taxable' => true, 'rush_eligible' => false, 'estimated_duration_minutes' => 10080],
            ['name' => 'Rush Service Add-on', 'category' => ServiceCategory::RUSH, 'pricing_type' => PricingType::FLAT_FEE, 'base_price' => '10.00', 'minimum_charge' => null, 'taxable' => true, 'rush_eligible' => false, 'estimated_duration_minutes' => 0],
        ];

        $out = [];
        foreach ($rows as $row) {
            $row['category'] = $row['category']->value;
            $row['pricing_type'] = $row['pricing_type']->value;
            $row['active'] = true;
            $service = Service::firstOrCreate(['name' => $row['name']], $row);
            $out[$service->name] = $service;
        }
        return $out;
    }

    private function seedDeliveryZones(): array
    {
        $rows = [
            ['name' => 'Downtown / Chelsea', 'description' => 'Core delivery radius, same-day eligible', 'fee' => '5.00'],
            ['name' => 'Uptown Express', 'description' => 'Upper Manhattan, next-day standard', 'fee' => '6.00'],
            ['name' => 'Outer Boroughs', 'description' => 'Brooklyn / Queens, 2-day standard', 'fee' => '8.50'],
        ];
        $out = [];
        foreach ($rows as $row) {
            $out[$row['name']] = DeliveryZone::firstOrCreate(
                ['name' => $row['name'], 'location_id' => $this->location->id],
                $row + ['location_id' => $this->location->id, 'active' => true]
            );
        }
        return $out;
    }

    private function seedInventory(array $suppliers): void
    {
        $rows = [
            ['name' => 'Liquid Detergent', 'unit' => 'bottle', 'current_quantity' => '40.00', 'reorder_threshold' => '10.00', 'cost_per_unit' => '6.50', 'supplier' => 'CleanChem Supply Co.'],
            ['name' => 'Fabric Softener', 'unit' => 'bottle', 'current_quantity' => '25.00', 'reorder_threshold' => '8.00', 'cost_per_unit' => '5.00', 'supplier' => 'CleanChem Supply Co.'],
            ['name' => 'Stain Remover Spray', 'unit' => 'bottle', 'current_quantity' => '15.00', 'reorder_threshold' => '5.00', 'cost_per_unit' => '4.25', 'supplier' => 'CleanChem Supply Co.'],
            ['name' => 'Dry Cleaning Solvent', 'unit' => 'gallon', 'current_quantity' => '30.00', 'reorder_threshold' => '10.00', 'cost_per_unit' => '18.00', 'supplier' => 'CleanChem Supply Co.'],
            ['name' => 'Poly Garment Bags (box of 250)', 'unit' => 'box', 'current_quantity' => '12.00', 'reorder_threshold' => '3.00', 'cost_per_unit' => '22.00', 'supplier' => 'PackRight Packaging Solutions'],
            ['name' => 'Wire Hangers (box of 500)', 'unit' => 'box', 'current_quantity' => '8.00', 'reorder_threshold' => '2.00', 'cost_per_unit' => '15.00', 'supplier' => 'PackRight Packaging Solutions'],
            ['name' => 'Laundry Tags (roll)', 'unit' => 'roll', 'current_quantity' => '3.00', 'reorder_threshold' => '5.00', 'cost_per_unit' => '3.50', 'supplier' => 'PackRight Packaging Solutions'],
        ];

        foreach ($rows as $row) {
            $supplier = $suppliers[$row['supplier']] ?? null;
            if (InventoryItem::where('name', $row['name'])->where('location_id', $this->location->id)->exists()) {
                continue;
            }

            $item = InventoryItem::create([
                'location_id' => $this->location->id,
                'supplier_id' => $supplier?->id,
                'name' => $row['name'],
                'unit' => $row['unit'],
                'current_quantity' => '0.00',
                'reorder_threshold' => $row['reorder_threshold'],
                'cost_per_unit' => $row['cost_per_unit'],
                'active' => true,
            ]);

            // Record the opening stock as a real RECEIVED transaction so the
            // item's current_quantity and its transaction history agree.
            $this->inventoryService->recordTransaction(
                $item,
                InventoryTransactionType::RECEIVED,
                $row['current_quantity'],
                'Opening stock',
                $this->manager,
            );
        }
    }

    private function seedCustomers(): array
    {
        $rows = [
            ['name' => 'Olivia Bennett', 'email' => 'olivia.bennett@gmail.com', 'phone' => '212-555-0101', 'address' => '245 E 21st St, Apt 4B', 'city' => 'New York', 'state' => 'NY', 'zip' => '10010'],
            ['name' => 'Marcus Reyes', 'email' => 'marcus.reyes@gmail.com', 'phone' => '212-555-0102', 'address' => '88 Lexington Ave, Apt 12', 'city' => 'New York', 'state' => 'NY', 'zip' => '10016'],
            ['name' => 'Priya Chandrasekaran', 'email' => 'priya.c@outlook.com', 'phone' => '646-555-0103', 'address' => '410 W 24th St, Apt 9', 'city' => 'New York', 'state' => 'NY', 'zip' => '10011'],
            ['name' => 'Daniel O\'Connor', 'email' => 'daniel.oconnor@yahoo.com', 'phone' => '917-555-0104', 'address' => '77 Greenwich St', 'city' => 'New York', 'state' => 'NY', 'zip' => '10006'],
            ['name' => 'Sofia Marchetti', 'email' => 'sofia.marchetti@gmail.com', 'phone' => '212-555-0105', 'address' => '150 W 17th St, Apt 3', 'city' => 'New York', 'state' => 'NY', 'zip' => '10011'],
            ['name' => 'Andre Thompson', 'email' => 'andre.thompson@gmail.com', 'phone' => '718-555-0106', 'address' => '512 Clinton Ave', 'city' => 'Brooklyn', 'state' => 'NY', 'zip' => '11238'],
            ['name' => 'Grace Kim', 'email' => 'grace.kim@gmail.com', 'phone' => '646-555-0107', 'address' => '19 Perry St', 'city' => 'New York', 'state' => 'NY', 'zip' => '10014'],
            ['name' => 'Michael Rossi', 'email' => 'michael.rossi@outlook.com', 'phone' => '212-555-0108', 'address' => '333 E 14th St, Apt 6', 'city' => 'New York', 'state' => 'NY', 'zip' => '10003'],
            ['name' => 'Aaliyah Jefferson', 'email' => 'aaliyah.jefferson@gmail.com', 'phone' => '917-555-0109', 'address' => '900 Fulton St', 'city' => 'Brooklyn', 'state' => 'NY', 'zip' => '11238'],
            ['name' => 'Benjamin Foster', 'email' => 'ben.foster@gmail.com', 'phone' => '212-555-0111', 'address' => '25 Union Sq W', 'city' => 'New York', 'state' => 'NY', 'zip' => '10003'],
            ['name' => 'Chloe Nguyen', 'email' => 'chloe.nguyen@gmail.com', 'phone' => '646-555-0112', 'address' => '210 W 20th St, Apt 5', 'city' => 'New York', 'state' => 'NY', 'zip' => '10011'],
            ['name' => 'Ethan Walsh', 'email' => 'ethan.walsh@yahoo.com', 'phone' => '212-555-0113', 'address' => '48 W 25th St', 'city' => 'New York', 'state' => 'NY', 'zip' => '10010'],
            ['name' => 'Isabella Moreno', 'email' => 'isabella.moreno@gmail.com', 'phone' => '917-555-0114', 'address' => '61-15 Roosevelt Ave', 'city' => 'Queens', 'state' => 'NY', 'zip' => '11377'],
            ['name' => 'Nathaniel Brooks', 'email' => 'nathaniel.brooks@gmail.com', 'phone' => '212-555-0115', 'address' => '350 W 22nd St', 'city' => 'New York', 'state' => 'NY', 'zip' => '10011'],
            ['name' => 'Ava Whitfield', 'email' => 'ava.whitfield@outlook.com', 'phone' => '646-555-0116', 'address' => '120 W 18th St, Apt 2', 'city' => 'New York', 'state' => 'NY', 'zip' => '10011'],
            ['name' => 'Jordan Michaels', 'email' => 'jordan.michaels@gmail.com', 'phone' => '212-555-0117', 'address' => '5 Beekman St', 'city' => 'New York', 'state' => 'NY', 'zip' => '10038'],
            ['name' => 'Layla Haddad', 'email' => 'layla.haddad@gmail.com', 'phone' => '718-555-0118', 'address' => '78 Bedford Ave', 'city' => 'Brooklyn', 'state' => 'NY', 'zip' => '11249'],
            ['name' => 'Samuel Greenberg', 'email' => 'samuel.greenberg@gmail.com', 'phone' => '212-555-0119', 'address' => '201 W 21st St, Apt 8', 'city' => 'New York', 'state' => 'NY', 'zip' => '10011'],
        ];

        $out = [];
        foreach ($rows as $row) {
            $customer = Customer::firstOrCreate(
                ['email' => $row['email']],
                [
                    'location_id' => $this->location->id,
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'address' => $row['address'],
                    'city' => $row['city'],
                    'state' => $row['state'],
                    'zip' => $row['zip'],
                    'operational_consent' => true,
                    'marketing_consent' => (bool) random_int(0, 1),
                    'notify_email' => true,
                    'notify_sms' => (bool) random_int(0, 1),
                    'active' => true,
                ]
            );
            $out[] = $customer;
        }
        return $out;
    }

    /**
     * Backdate an order (and its items + status-history rows) to look like it
     * really happened on $date, since OrderService/OrderStatusService always
     * stamp "now" — this keeps reports/dashboard date filtering realistic.
     */
    private function backdateOrder(Order $order, Carbon $date): void
    {
        DB::table('orders')->where('id', $order->id)->update([
            'intake_at' => $date,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('order_items')->where('order_id', $order->id)->update([
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $historyIds = DB::table('order_status_histories')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->pluck('id');

        foreach ($historyIds as $i => $id) {
            DB::table('order_status_histories')->where('id', $id)->update([
                'created_at' => (clone $date)->addHours($i * 6),
            ]);
        }
    }

    private function seedOrders(array $customers, array $services, array $zones): void
    {
        if (Order::count() > 0) {
            $this->command?->info('Orders already exist, skipping order seeding.');
            return;
        }

        $wash = $services['Wash and Fold'];
        $press = $services['Wash and Press'];
        $shirt = $services['Dry Cleaning - Shirt'];
        $suit = $services['Dry Cleaning - Suit (2pc)'];
        $dress = $services['Dry Cleaning - Dress'];
        $alterations = $services['Alterations & Repairs'];
        $shoes = $services['Shoe Cleaning'];
        $weddingDress = $services['Wedding Dress Preservation'];
        $rush = $services['Rush Service Add-on'];

        $today = Carbon::now();
        $customerIdx = 0;
        $nextCustomer = function () use ($customers, &$customerIdx) {
            $c = $customers[$customerIdx % count($customers)];
            $customerIdx++;
            return $c;
        };

        $createdBy = $this->cashier;

        // --- Scenario builder -------------------------------------------------
        $makeOrder = function (
            array $items,
            Carbon $date,
            string $channel = 'walk_in',
            bool $rushFlag = false,
            float $discount = 0,
            ?float $weight = null,
            ?int $itemCount = null,
        ) use ($nextCustomer, $createdBy) {
            $customer = $nextCustomer();
            $order = $this->orderService->createOrder([
                'customer_id' => $customer->id,
                'location_id' => $this->location->id,
                'intake_channel' => $channel,
                'promised_at' => (clone $date)->addDays(2),
                'rush' => $rushFlag,
                'weight_lbs' => $weight,
                'item_count' => $itemCount,
                'discount_amount' => $discount,
                'items' => $items,
            ], $createdBy->id);
            $this->backdateOrder($order, $date);
            return $order->fresh();
        };

        $advance = function (Order $order, array $path, ?User $actor = null) {
            $actor = $actor ?? $this->staff;
            foreach ($path as $status) {
                $order = $this->orderStatusService->transition($order, OrderStatus::from($status), $actor);
            }
            return $order->fresh();
        };

        // 10 completed orders, spread over the last 6 weeks, fully paid.
        $completedScenarios = [
            [[['service_id' => $wash->id, 'quantity' => 18]], 40, 'walk_in', 18.0, null],
            [[['service_id' => $press->id, 'quantity' => 12], ['service_id' => $shirt->id, 'quantity' => 3]], 35, 'walk_in', 12.0, null],
            [[['service_id' => $shirt->id, 'quantity' => 6]], 30, 'walk_in', null, 6],
            [[['service_id' => $suit->id, 'quantity' => 1], ['service_id' => $shirt->id, 'quantity' => 2]], 27, 'walk_in', null, 3],
            [[['service_id' => $wash->id, 'quantity' => 24]], 24, 'pickup', 24.0, null],
            [[['service_id' => $dress->id, 'quantity' => 2]], 20, 'walk_in', null, 2],
            [[['service_id' => $alterations->id, 'quantity' => 1.5]], 17, 'walk_in', null, null],
            [[['service_id' => $wash->id, 'quantity' => 15], ['service_id' => $rush->id, 'quantity' => 1]], 14, 'walk_in', 15.0, null],
            [[['service_id' => $shoes->id, 'quantity' => 1], ['service_id' => $shoes->id, 'quantity' => 1]], 10, 'walk_in', null, 2],
            [[['service_id' => $press->id, 'quantity' => 20]], 6, 'delivery', 20.0, null],
        ];
        foreach ($completedScenarios as [$items, $daysAgo, $channel, $weight, $itemCount]) {
            $order = $makeOrder($items, (clone $today)->subDays($daysAgo), $channel, weight: $weight, itemCount: $itemCount);
            $order = $advance($order, ['tagged', 'sorting', 'washing', 'drying', 'finishing', 'quality_check', 'ready_for_pickup', 'completed']);
            $this->paymentService->recordPayment($order, (string) $order->total, PaymentMethod::CASH->value, 'Paid in full at pickup', $this->cashier);
        }

        // 1 completed order with a voided duplicate payment, then the real one.
        $order = $makeOrder([['service_id' => $wash->id, 'quantity' => 16]], (clone $today)->subDays(22), weight: 16.0);
        $order = $advance($order, ['tagged', 'sorting', 'washing', 'drying', 'finishing', 'quality_check', 'ready_for_pickup', 'completed']);
        $dupPayment = $this->paymentService->recordPayment($order, (string) $order->total, PaymentMethod::CASH->value, 'Payment entered twice by mistake', $this->cashier);
        $this->paymentService->voidPayment($dupPayment, 'Duplicate payment entry - processed in error', $this->manager);
        $this->paymentService->recordPayment($order->fresh(), (string) $order->fresh()->balance_due, PaymentMethod::EXTERNAL->value, 'Paid via card terminal', $this->cashier);

        // 1 completed order with a small partial refund.
        $order = $makeOrder([['service_id' => $press->id, 'quantity' => 10], ['service_id' => $shirt->id, 'quantity' => 2]], (clone $today)->subDays(9), weight: 10.0);
        $order = $advance($order, ['tagged', 'sorting', 'washing', 'drying', 'finishing', 'quality_check', 'ready_for_pickup', 'completed']);
        $payment = $this->paymentService->recordPayment($order, (string) $order->total, PaymentMethod::CASH->value, 'Paid in full', $this->cashier);
        $this->paymentService->refundPayment($payment, '8.00', 'Customer complaint - light stain not fully removed on one shirt', $this->manager);

        // 2 orders ready for pickup, paid in full, waiting for the customer.
        foreach ([2, 1] as $daysAgo) {
            $order = $makeOrder([['service_id' => $wash->id, 'quantity' => 13]], (clone $today)->subDays($daysAgo), weight: 13.0);
            $order = $advance($order, ['tagged', 'sorting', 'washing', 'drying', 'finishing', 'quality_check', 'ready_for_pickup']);
            $this->paymentService->recordPayment($order, (string) $order->total, PaymentMethod::CASH->value, 'Paid at intake', $this->cashier);
        }

        // 2 orders out for delivery, paid, with a Delivery record.
        $zoneList = array_values($zones);
        foreach ([1, 0] as $i => $daysAgo) {
            $order = $makeOrder([['service_id' => $wash->id, 'quantity' => 20]], (clone $today)->subDays($daysAgo), channel: 'delivery', weight: 20.0);
            $order = $advance($order, ['tagged', 'sorting', 'washing', 'drying', 'finishing', 'quality_check', 'ready_for_pickup', 'out_for_delivery']);
            $this->paymentService->recordPayment($order, (string) $order->total, PaymentMethod::EXTERNAL->value, 'Paid online', $this->cashier);
            $zone = $zoneList[$i % count($zoneList)];
            $customerForDelivery = $order->customer;
            $delivery = $this->deliveryService->schedule($order, [
                'delivery_zone_id' => $zone->id,
                'type' => DeliveryType::DELIVERY->value,
                'scheduled_at' => (clone $today)->addHours(4),
                'address' => $customerForDelivery->address,
                'city' => $customerForDelivery->city,
                'state' => $customerForDelivery->state,
                'zip' => $customerForDelivery->zip,
                'driver_id' => $this->driver->id,
            ], $this->manager);
            if ($daysAgo === 1) {
                $this->deliveryService->updateStatus($delivery, DeliveryStatus::COMPLETED, 'Left with doorman, signed for by building staff', $this->driver);
            } else {
                $this->deliveryService->updateStatus($delivery, DeliveryStatus::EN_ROUTE, null, $this->driver);
            }
        }

        // 4 orders mid-production, unpaid or partially paid.
        $midProduction = [
            [[['service_id' => $wash->id, 'quantity' => 22]], 3, ['tagged', 'sorting', 'washing'], 22.0, null],
            [[['service_id' => $suit->id, 'quantity' => 2]], 2, ['tagged', 'sorting', 'washing', 'drying', 'finishing'], null, 4],
            [[['service_id' => $shirt->id, 'quantity' => 8]], 1, ['tagged', 'sorting'], null, 8],
            [[['service_id' => $weddingDress->id, 'quantity' => 1]], 5, ['tagged'], null, 1],
        ];
        foreach ($midProduction as [$items, $daysAgo, $path, $weight, $itemCount]) {
            $order = $makeOrder($items, (clone $today)->subDays($daysAgo), weight: $weight, itemCount: $itemCount);
            $order = $advance($order, $path);
            if (random_int(0, 1) === 1) {
                $deposit = bcmul((string) $order->total, '0.5', 2);
                $this->paymentService->recordPayment($order, $deposit, PaymentMethod::CASH->value, 'Deposit at intake', $this->cashier);
            }
        }

        // 2 freshly checked-in orders today, unpaid.
        foreach ([['service_id' => $wash->id, 'quantity' => 9], ['service_id' => $shirt->id, 'quantity' => 4]] as $line) {
            $this->makeOrderToday([$line], $today, $nextCustomer, $createdBy);
        }

        // 2 cancelled orders.
        $order = $makeOrder([['service_id' => $press->id, 'quantity' => 8]], (clone $today)->subDays(11), weight: 8.0);
        $this->orderStatusService->transition($order, OrderStatus::CANCELLED, $this->manager);
        $order = $makeOrder([['service_id' => $shirt->id, 'quantity' => 3]], (clone $today)->subDays(4), itemCount: 3);
        $this->orderStatusService->transition($order, OrderStatus::CANCELLED, $this->manager);

        // 2 on-hold orders (customer unreachable / disputed item).
        $order = $makeOrder([['service_id' => $wash->id, 'quantity' => 11]], (clone $today)->subDays(6), weight: 11.0);
        $order = $advance($order, ['tagged', 'sorting']);
        $this->orderStatusService->transition($order, OrderStatus::ON_HOLD, $this->manager, 'Customer unreachable to confirm a stained item - awaiting callback');
        $order = $makeOrder([['service_id' => $suit->id, 'quantity' => 1]], (clone $today)->subDays(2), itemCount: 1);
        $this->orderStatusService->transition($order, OrderStatus::ON_HOLD, $this->manager, 'Missing button found during quality check, sourcing a replacement');
    }

    private function makeOrderToday(array $items, Carbon $today, \Closure $nextCustomer, User $createdBy): Order
    {
        $customer = $nextCustomer();
        $order = $this->orderService->createOrder([
            'customer_id' => $customer->id,
            'location_id' => $this->location->id,
            'intake_channel' => IntakeChannel::WALK_IN->value,
            'promised_at' => (clone $today)->addDays(2),
            'rush' => false,
            'items' => $items,
        ], $createdBy->id);
        return $order->fresh();
    }

    private function seedExpenses(array $categories, array $suppliers): void
    {
        if (Expense::count() > 0) {
            return;
        }

        $today = Carbon::now();
        $rows = [
            ['category' => 'Rent', 'amount' => '3500.00', 'description' => 'Monthly storefront rent', 'daysAgo' => 28, 'supplier' => null],
            ['category' => 'Utilities', 'amount' => '410.25', 'description' => 'Electric & water bill', 'daysAgo' => 25, 'supplier' => null],
            ['category' => 'Detergent & Supplies', 'amount' => '286.00', 'description' => 'Monthly detergent and softener restock', 'daysAgo' => 20, 'supplier' => 'CleanChem Supply Co.'],
            ['category' => 'Equipment Maintenance', 'amount' => '150.00', 'description' => 'Dryer belt replacement, Machine #3', 'daysAgo' => 18, 'supplier' => 'ProLaundry Equipment Parts'],
            ['category' => 'Payroll', 'amount' => '4200.00', 'description' => 'Bi-weekly staff payroll', 'daysAgo' => 14, 'supplier' => null],
            ['category' => 'Marketing & Advertising', 'amount' => '120.00', 'description' => 'Local neighborhood flyer printing', 'daysAgo' => 12, 'supplier' => null],
            ['category' => 'Detergent & Supplies', 'amount' => '198.50', 'description' => 'Dry cleaning solvent restock', 'daysAgo' => 10, 'supplier' => 'CleanChem Supply Co.'],
            ['category' => 'Equipment Maintenance', 'amount' => '95.00', 'description' => 'Press machine steam valve repair', 'daysAgo' => 9, 'supplier' => 'ProLaundry Equipment Parts'],
            ['category' => 'Detergent & Supplies', 'amount' => '76.00', 'description' => 'Poly bags and hangers restock', 'daysAgo' => 7, 'supplier' => 'PackRight Packaging Solutions'],
            ['category' => 'Utilities', 'amount' => '95.00', 'description' => 'Internet & phone service', 'daysAgo' => 5, 'supplier' => null],
            ['category' => 'Marketing & Advertising', 'amount' => '60.00', 'description' => 'Google Business Profile boost', 'daysAgo' => 3, 'supplier' => null],
            ['category' => 'Payroll', 'amount' => '4350.00', 'description' => 'Bi-weekly staff payroll', 'daysAgo' => 1, 'supplier' => null],
        ];

        foreach ($rows as $row) {
            Expense::create([
                'location_id' => $this->location->id,
                'expense_category_id' => $categories[$row['category']]->id,
                'supplier_id' => $row['supplier'] ? $suppliers[$row['supplier']]->id : null,
                'amount' => $row['amount'],
                'description' => $row['description'],
                'expense_date' => (clone $today)->subDays($row['daysAgo'])->toDateString(),
                'recorded_by' => $this->accountant->id,
            ]);
        }
    }
}
