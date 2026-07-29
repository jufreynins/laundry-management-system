<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OnlinePayment\PaymentProvider;
use App\Services\PaymentException;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class OnlinePaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentProvider $paymentProvider,
    ) {
    }

    public function store(Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        $this->authorize('create', Payment::class);

        try {
            $result = $this->paymentService->initiateOnlineCheckout($order, $this->paymentProvider);
        } catch (PaymentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->away($result['checkout_url']);
    }
}
