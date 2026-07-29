<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    public function transition(Order $order, OrderStatus $to, User $actor, ?string $reason = null, bool $override = false): Order
    {
        $from = $order->status;

        if (!$override && !OrderStatusTransitions::isAllowed($from, $to)) {
            throw new OrderStatusException(
                "Cannot transition order from \"{$from->label()}\" to \"{$to->label()}\" without an authorized override."
            );
        }

        if ($override && empty(trim((string) $reason))) {
            throw new OrderStatusException('An override reason is required.');
        }

        return DB::transaction(function () use ($order, $from, $to, $actor, $reason, $override) {
            $order->update(['status' => $to->value]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_by' => $actor->id,
                'reason' => $reason,
                'is_override' => $override,
                'created_at' => now(),
            ]);

            AuditLogService::record(
                $override ? AuditAction::OVERRIDE_STATUS : AuditAction::UPDATED,
                $order,
                ['status' => $from->value],
                ['status' => $to->value],
                $reason,
                $order->location_id,
            );

            return $order->fresh();
        });
    }

    public function assignStaff(Order $order, ?int $userId, User $actor): Order
    {
        $oldUserId = $order->assigned_user_id;

        $order->update(['assigned_user_id' => $userId]);

        AuditLogService::record(
            AuditAction::UPDATED,
            $order,
            ['assigned_user_id' => $oldUserId],
            ['assigned_user_id' => $userId],
            'Staff assignment changed',
            $order->location_id,
        );

        return $order->fresh();
    }
}
