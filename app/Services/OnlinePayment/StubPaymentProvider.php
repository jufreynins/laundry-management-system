<?php

namespace App\Services\OnlinePayment;

use App\Models\Order;

/**
 * MVP placeholder implementation of PaymentProvider. Simulates a hosted
 * checkout (the "checkout" is a local route showing Success/Fail buttons
 * that POST a correctly signed webhook, exactly like a real provider
 * server-to-server callback would). No card data is ever collected here.
 *
 * Swap the PaymentProvider binding in AppServiceProvider for a real vendor
 * (Stripe, Square, etc.) when one is chosen — OnlinePaymentController and
 * the webhook controller depend only on this interface.
 */
class StubPaymentProvider implements PaymentProvider
{
    public function name(): string
    {
        return 'stub';
    }

    public function createCheckoutSession(Order $order, string $providerTransactionId, string $successUrl, string $cancelUrl): string
    {
        return route('online-payments.checkout.show', $providerTransactionId);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->secret());

        return hash_equals($expected, $signature);
    }

    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret());
    }

    public function parseWebhookPayload(string $payload): array
    {
        return json_decode($payload, true) ?? [];
    }

    public function refund(string $providerTransactionId, string $amount): bool
    {
        // Stub: always succeeds. A real provider would call its refund API here.
        return true;
    }

    private function secret(): string
    {
        return config('services.stub_payment.webhook_secret', 'stub-dev-secret-change-me');
    }
}
