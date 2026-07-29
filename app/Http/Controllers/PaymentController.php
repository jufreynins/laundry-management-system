<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefundPaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\VoidPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OnlinePayment\PaymentProvider;
use App\Services\PaymentException;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentProvider $paymentProvider,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $user = $request->user();
        $query = Payment::query()->with(['order', 'recordedBy'])->latest('created_at');

        if ($locationId = $user->scopedLocationId()) {
            $query->where('location_id', $locationId);
        }

        $payments = $query->paginate(20);

        return view('payments.index', ['payments' => $payments]);
    }

    public function store(StorePaymentRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        try {
            $this->paymentService->recordPayment(
                $order,
                number_format((float) $request->validated('amount'), 2, '.', ''),
                $request->validated('method'),
                $request->validated('reference_note'),
                $request->user(),
                $request->validated('idempotency_key'),
            );
        } catch (PaymentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', 'Payment recorded successfully.');
    }

    public function void(VoidPaymentRequest $request, Payment $payment): RedirectResponse
    {
        try {
            $this->paymentService->voidPayment($payment, $request->validated('reason'), $request->user());
        } catch (PaymentException $e) {
            return back()->withErrors(['reason' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $payment->order)->with('status', 'Payment voided.');
    }

    public function refund(RefundPaymentRequest $request, Payment $payment): RedirectResponse
    {
        try {
            if ($payment->provider && $payment->provider_transaction_id) {
                $this->paymentProvider->refund(
                    $payment->provider_transaction_id,
                    number_format((float) $request->validated('amount'), 2, '.', ''),
                );
            }

            $this->paymentService->refundPayment(
                $payment,
                number_format((float) $request->validated('amount'), 2, '.', ''),
                $request->validated('reason'),
                $request->user(),
            );
        } catch (PaymentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $payment->order)->with('status', 'Refund issued.');
    }
}
