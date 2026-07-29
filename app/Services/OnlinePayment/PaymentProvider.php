<?php

namespace App\Services\OnlinePayment;

use App\Models\Order;

interface PaymentProvider
{
    public function name(): string;

    /**
     * Create a hosted checkout session. The customer is redirected to
     * checkoutUrl on the provider's own domain to enter card details —
     * this application never collects or stores raw card data.
     */
    public function createCheckoutSession(Order $order, string $providerTransactionId, string $successUrl, string $cancelUrl): string;

    /**
     * Verify a webhook payload's signature came from this provider.
     * Must be constant-time and reject on any mismatch.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Parse a verified webhook payload into a normalized event array:
     * ['type' => 'payment_succeeded'|'payment_failed'|'refund_succeeded',
     *  'provider_transaction_id' => string, 'amount' => ?string,
     *  'payment_method_brand' => ?string, 'last_four' => ?string,
     *  'receipt_url' => ?string, 'refund_amount' => ?string]
     */
    public function parseWebhookPayload(string $payload): array;

    public function refund(string $providerTransactionId, string $amount): bool;
}
