<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\OnlinePayment\StubPaymentProvider;
use Illuminate\View\View;

/**
 * Simulated hosted-checkout page for the MVP StubPaymentProvider. Stands
 * in for the real provider-hosted page a customer would see with a real
 * vendor. Renders forms that POST correctly signed webhook payloads —
 * this exercises the exact same signature-verification and idempotency
 * path a real provider's server-to-server callback would.
 */
class StubCheckoutController extends Controller
{
    public function show(string $providerTransactionId, StubPaymentProvider $provider): View
    {
        $payment = Payment::where('provider_transaction_id', $providerTransactionId)->firstOrFail();

        $successPayload = json_encode([
            'type' => 'payment_succeeded',
            'provider_transaction_id' => $providerTransactionId,
            'payment_method_brand' => 'visa',
            'last_four' => '4242',
            'receipt_url' => null,
        ]);

        $failedPayload = json_encode([
            'type' => 'payment_failed',
            'provider_transaction_id' => $providerTransactionId,
        ]);

        return view('online-payments.stub-checkout', [
            'payment' => $payment,
            'successPayload' => $successPayload,
            'successSignature' => $provider->sign($successPayload),
            'failedPayload' => $failedPayload,
            'failedSignature' => $provider->sign($failedPayload),
        ]);
    }
}
