<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('orders.receipt', [
            'order' => $order->load(['items', 'customer', 'location', 'payments' => fn ($q) => $q->where('status', '!=', 'voided')]),
        ]);
    }

    public function claimTicket(Order $order): View
    {
        $this->authorize('view', $order);

        return view('orders.claim-ticket', ['order' => $order->load(['items', 'customer', 'location'])]);
    }
}
