<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function ordersToday(?int $locationId): int
    {
        return $this->scopedOrders($locationId)
            ->whereDate('intake_at', now()->toDateString())
            ->count();
    }

    public function revenueToday(?int $locationId): string
    {
        return number_format((float) $this->scopedPayments($locationId)
            ->where('status', '!=', 'voided')
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount'), 2, '.', '');
    }

    public function totalAmountDue(?int $locationId): string
    {
        return number_format((float) $this->scopedOrders($locationId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->sum('balance_due'), 2, '.', '');
    }

    public function ordersByStatus(?int $locationId): array
    {
        return $this->scopedOrders($locationId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    public function readyForPickupCount(?int $locationId): int
    {
        return $this->scopedOrders($locationId)->where('status', 'ready_for_pickup')->count();
    }

    public function overdueOrders(?int $locationId, int $limit = 20)
    {
        return $this->scopedOrders($locationId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('promised_at')
            ->where('promised_at', '<', now())
            ->with('customer')
            ->orderBy('promised_at')
            ->limit($limit)
            ->get();
    }

    public function salesByService(?int $locationId, Carbon $from, Carbon $to): array
    {
        $query = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.intake_at', [$from, $to]);

        if ($locationId) {
            $query->where('orders.location_id', $locationId);
        }

        return $query
            ->select('order_items.description', DB::raw('sum(order_items.line_total) as total'), DB::raw('count(*) as line_count'))
            ->groupBy('order_items.description')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public function salesByLocation(Carbon $from, Carbon $to): array
    {
        return Order::query()
            ->join('locations', 'locations.id', '=', 'orders.location_id')
            ->whereBetween('orders.intake_at', [$from, $to])
            ->select('locations.name', DB::raw('sum(orders.total) as total'), DB::raw('count(*) as order_count'))
            ->groupBy('locations.id', 'locations.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public function taxSummary(?int $locationId, Carbon $from, Carbon $to): string
    {
        $query = Order::query()->whereBetween('intake_at', [$from, $to]);
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return number_format((float) $query->sum('tax_amount'), 2, '.', '');
    }

    public function paymentSummaryByMethod(?int $locationId, Carbon $from, Carbon $to): array
    {
        $query = Payment::query()
            ->where('status', '!=', 'voided')
            ->whereBetween('created_at', [$from, $to]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query
            ->select('method', DB::raw('sum(amount) as total'), DB::raw('count(*) as payment_count'))
            ->groupBy('method')
            ->get()
            ->toArray();
    }

    private function scopedOrders(?int $locationId)
    {
        $query = Order::query();
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query;
    }

    private function scopedPayments(?int $locationId)
    {
        $query = Payment::query();
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query;
    }
}
