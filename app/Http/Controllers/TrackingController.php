<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class TrackingController extends Controller
{
    /**
     * Public, unauthenticated order-tracking page. Deliberately exposes
     * only customer-safe fields — never full address/phone/email, employee
     * names, internal notes, intake photos, database IDs, or financial
     * audit detail. See docs/SECURITY_CHECKLIST.md.
     */
    public function show(string $token): View
    {
        $order = Order::where('tracking_token', $token)->firstOrFail();

        $latestDelivery = $order->deliveries()->latest('scheduled_at')->first();

        return view('public.tracking', [
            'orderNumber' => $order->order_number,
            'status' => $order->status->customerLabel(),
            'promisedAt' => $order->promised_at,
            'deliveryType' => $latestDelivery?->type->label(),
            'deliveryStatus' => $latestDelivery?->status->label(),
            'balanceDue' => $order->balance_due,
            'storeName' => $order->location->name,
            'storePhone' => $order->location->phone,
        ]);
    }
}
