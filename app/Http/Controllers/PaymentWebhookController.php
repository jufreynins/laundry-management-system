<?php

namespace App\Http\Controllers;

use App\Services\OnlinePayment\PaymentProvider;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentProvider $paymentProvider,
        private readonly PaymentService $paymentService,
    ) {
    }

    /**
     * Payment-provider webhook receiver. Unauthenticated and CSRF-exempt
     * (see bootstrap/app.php) since it's called server-to-server by the
     * payment provider, not by a logged-in browser session. The signature
     * check is what authenticates the caller — never trust this endpoint
     * without it. Idempotent: safe to receive the same event twice.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = (string) $request->input('payload');
        $signature = (string) $request->input('signature');

        if ($payload === '' || $signature === '' || !$this->paymentProvider->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Payment webhook rejected: invalid or missing signature');

            return response()->json(['error' => 'invalid signature'], 400);
        }

        $event = $this->paymentProvider->parseWebhookPayload($payload);
        $type = $event['type'] ?? null;
        $providerTransactionId = $event['provider_transaction_id'] ?? null;

        if (!$providerTransactionId) {
            return response()->json(['error' => 'missing provider_transaction_id'], 400);
        }

        match ($type) {
            'payment_succeeded' => $this->paymentService->confirmOnlinePaymentSucceeded($providerTransactionId, $event),
            'payment_failed' => $this->paymentService->markOnlinePaymentFailed($providerTransactionId),
            default => Log::info('Unhandled payment webhook event type', ['type' => $type]),
        };

        return response()->json(['status' => 'ok']);
    }
}
