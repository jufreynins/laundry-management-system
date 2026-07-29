<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\OrderStatus;
use App\Enums\PricingType;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create an order and its line items inside a single database transaction.
     * All pricing is computed server-side from authoritative Service records —
     * the caller supplies only service_id + quantity per line, never a price.
     *
     * @param array{
     *   customer_id:int, location_id:int, intake_channel:string, promised_at:?string,
     *   rush:bool, assigned_user_id:?int, weight_lbs:?float, item_count:?int, bag_count:?int,
     *   stain_notes:?string, customer_instructions:?string, internal_notes:?string,
     *   discount_amount:?float, tip_amount:?float,
     *   items: array<int, array{service_id:int, quantity:float}>
     * } $data
     */
    public function createOrder(array $data, int $createdByUserId): Order
    {
        if (empty($data['items'])) {
            throw new OrderPricingException('An order must contain at least one service item.');
        }

        return DB::transaction(function () use ($data, $createdByUserId) {
            $location = Location::findOrFail($data['location_id']);

            $lineItems = [];
            $subtotal = '0.00';

            foreach ($data['items'] as $itemInput) {
                $service = Service::where('active', true)->findOrFail($itemInput['service_id']);
                $quantity = (string) $itemInput['quantity'];

                $unitPrice = $service->priceForLocation($location->id);
                $lineTotal = $this->calculateLineTotal($service->pricing_type, $quantity, $unitPrice, $service->minimum_charge);

                $lineItems[] = [
                    'service_id' => $service->id,
                    'description' => $service->name,
                    'pricing_type' => $service->pricing_type->value,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'taxable' => $service->taxable,
                ];

                $subtotal = bcadd($subtotal, $lineTotal, 2);
            }

            $discountAmount = number_format((float) ($data['discount_amount'] ?? 0), 2, '.', '');
            if (bccomp($discountAmount, $subtotal, 2) > 0) {
                throw new OrderPricingException('Discount cannot exceed the order subtotal.');
            }

            $taxableBase = '0.00';
            foreach ($lineItems as $line) {
                if ($line['taxable']) {
                    $taxableBase = bcadd($taxableBase, $line['line_total'], 2);
                }
            }
            $discountRatio = bccomp($subtotal, '0.00', 2) > 0 ? bcdiv($discountAmount, $subtotal, 6) : '0';
            $taxableAfterDiscount = bcsub($taxableBase, bcmul($taxableBase, $discountRatio, 6), 2);

            $taxRate = $location->setting('sales_tax_enabled') === '1' ? (float) ($location->setting('tax_rate') ?? 0) : 0;
            $taxAmount = bcmul($taxableAfterDiscount, (string) ($taxRate / 100), 2);

            $tipAmount = number_format((float) ($data['tip_amount'] ?? 0), 2, '.', '');

            $total = bcadd(bcadd(bcsub($subtotal, $discountAmount, 2), $taxAmount, 2), $tipAmount, 2);

            $order = Order::create([
                'customer_id' => $data['customer_id'],
                'location_id' => $location->id,
                'intake_channel' => $data['intake_channel'],
                'status' => OrderStatus::CHECKED_IN->value,
                'intake_at' => now(),
                'promised_at' => $data['promised_at'] ?? null,
                'rush' => $data['rush'] ?? false,
                'assigned_user_id' => $data['assigned_user_id'] ?? null,
                'created_by' => $createdByUserId,
                'weight_lbs' => $data['weight_lbs'] ?? null,
                'item_count' => $data['item_count'] ?? null,
                'customer_declared_item_count' => $data['customer_declared_item_count'] ?? null,
                'bag_count' => $data['bag_count'] ?? null,
                'stain_notes' => $data['stain_notes'] ?? null,
                'garment_flags' => $data['garment_flags'] ?? null,
                'customer_acknowledged' => $data['customer_acknowledged'] ?? false,
                'customer_instructions' => $data['customer_instructions'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'tip_amount' => $tipAmount,
                'total' => $total,
                'amount_paid' => '0.00',
                'balance_due' => $total,
            ]);

            foreach ($lineItems as $line) {
                $line['order_id'] = $order->id;
                OrderItem::create($line);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => OrderStatus::CHECKED_IN->value,
                'changed_by' => $createdByUserId,
                'created_at' => now(),
            ]);

            AuditLogService::record(
                AuditAction::CREATED,
                $order,
                null,
                ['order_number' => $order->order_number, 'total' => $total],
                locationId: $location->id,
            );

            return $order->fresh(['items', 'customer', 'location']);
        });
    }

    private function calculateLineTotal(PricingType $pricingType, string $quantity, string $unitPrice, ?string $minimumCharge): string
    {
        $lineTotal = match ($pricingType) {
            PricingType::PER_POUND, PricingType::PER_ITEM, PricingType::HOURLY => bcmul($quantity, $unitPrice, 2),
            PricingType::FLAT_FEE, PricingType::CUSTOM_QUOTE => $unitPrice,
        };

        if ($minimumCharge !== null && bccomp($lineTotal, $minimumCharge, 2) < 0) {
            $lineTotal = $minimumCharge;
        }

        return $lineTotal;
    }
}
