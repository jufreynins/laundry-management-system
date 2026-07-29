<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Record a cash or manual-external payment against an order.
     * Wrapped in a transaction; prevents overpayment and duplicate submission.
     */
    public function recordPayment(Order $order, string $amount, string $method, ?string $referenceNote, User $actor, ?string $idempotencyKey = null): Payment
    {
        if (bccomp($amount, '0.00', 2) <= 0) {
            throw new PaymentException('Payment amount must be greater than zero.');
        }

        if ($idempotencyKey !== null && Payment::where('idempotency_key', $idempotencyKey)->exists()) {
            throw new PaymentException('This payment has already been submitted.');
        }

        return DB::transaction(function () use ($order, $amount, $method, $referenceNote, $actor, $idempotencyKey) {
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            if (bccomp($amount, (string) $order->balance_due, 2) > 0) {
                throw new PaymentException('Payment amount cannot exceed the remaining balance due.');
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'location_id' => $order->location_id,
                'method' => $method,
                'status' => PaymentStatus::COMPLETED->value,
                'amount' => $amount,
                'reference_note' => $referenceNote,
                'recorded_by' => $actor->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            $order->update([
                'amount_paid' => bcadd((string) $order->amount_paid, $amount, 2),
                'balance_due' => bcsub((string) $order->balance_due, $amount, 2),
            ]);

            AuditLogService::record(
                AuditAction::PAYMENT_RECORDED,
                $payment,
                null,
                ['amount' => $amount, 'method' => $method],
                locationId: $order->location_id,
            );

            return $payment->fresh();
        });
    }

    public function voidPayment(Payment $payment, string $reason, User $actor): Payment
    {
        if ($payment->status !== PaymentStatus::COMPLETED) {
            throw new PaymentException('Only a completed payment with no refunds can be voided.');
        }

        if (empty(trim($reason))) {
            throw new PaymentException('A reason is required to void a payment.');
        }

        return DB::transaction(function () use ($payment, $reason, $actor) {
            $order = Order::where('id', $payment->order_id)->lockForUpdate()->first();

            $payment->update([
                'status' => PaymentStatus::VOIDED->value,
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => $reason,
            ]);

            $order->update([
                'amount_paid' => bcsub((string) $order->amount_paid, (string) $payment->amount, 2),
                'balance_due' => bcadd((string) $order->balance_due, (string) $payment->amount, 2),
            ]);

            AuditLogService::record(
                AuditAction::UPDATED,
                $payment,
                ['status' => PaymentStatus::COMPLETED->value],
                ['status' => PaymentStatus::VOIDED->value],
                $reason,
                $order->location_id,
            );

            return $payment->fresh();
        });
    }

    public function refundPayment(Payment $payment, string $amount, string $reason, User $actor): Refund
    {
        if (bccomp($amount, '0.00', 2) <= 0) {
            throw new PaymentException('Refund amount must be greater than zero.');
        }

        if (empty(trim($reason))) {
            throw new PaymentException('A reason is required to issue a refund.');
        }

        return DB::transaction(function () use ($payment, $amount, $reason, $actor) {
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();
            $order = Order::where('id', $payment->order_id)->lockForUpdate()->first();

            $refundable = $payment->refundableAmount();
            if (bccomp($amount, $refundable, 2) > 0) {
                throw new PaymentException('Refund amount cannot exceed the refundable balance of this payment.');
            }

            $refund = Refund::create([
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'amount' => $amount,
                'reason' => $reason,
                'processed_by' => $actor->id,
                'created_at' => now(),
            ]);

            $remainingAfter = bcsub($refundable, $amount, 2);
            $payment->update([
                'status' => bccomp($remainingAfter, '0.00', 2) === 0
                    ? PaymentStatus::REFUNDED->value
                    : PaymentStatus::PARTIALLY_REFUNDED->value,
            ]);

            $order->update([
                'amount_paid' => bcsub((string) $order->amount_paid, $amount, 2),
                'balance_due' => bcadd((string) $order->balance_due, $amount, 2),
            ]);

            AuditLogService::record(
                AuditAction::REFUND_ISSUED,
                $refund,
                null,
                ['amount' => $amount, 'payment_id' => $payment->id],
                $reason,
                $order->location_id,
            );

            return $refund->fresh();
        });
    }
}
