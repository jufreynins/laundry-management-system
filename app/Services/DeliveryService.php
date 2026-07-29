<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    public function schedule(Order $order, array $data, User $actor): Delivery
    {
        return DB::transaction(function () use ($order, $data, $actor) {
            $fee = '0.00';
            if (!empty($data['delivery_zone_id'])) {
                $zone = DeliveryZone::findOrFail($data['delivery_zone_id']);
                $fee = (string) $zone->fee;
            }

            $delivery = Delivery::create([
                'order_id' => $order->id,
                'location_id' => $order->location_id,
                'delivery_zone_id' => $data['delivery_zone_id'] ?? null,
                'type' => $data['type'],
                'status' => DeliveryStatus::SCHEDULED->value,
                'scheduled_at' => $data['scheduled_at'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip' => $data['zip'],
                'driver_id' => $data['driver_id'] ?? null,
                'fee' => $fee,
                'created_by' => $actor->id,
            ]);

            AuditLogService::record(
                AuditAction::CREATED,
                $delivery,
                null,
                ['order_id' => $order->id, 'type' => $data['type']],
                locationId: $order->location_id,
            );

            return $delivery->fresh();
        });
    }

    public function assignDriver(Delivery $delivery, ?int $driverId, User $actor): Delivery
    {
        $old = $delivery->driver_id;

        $delivery->update(['driver_id' => $driverId]);

        AuditLogService::record(
            AuditAction::UPDATED,
            $delivery,
            ['driver_id' => $old],
            ['driver_id' => $driverId],
            'Driver assignment changed',
            $delivery->location_id,
        );

        return $delivery->fresh();
    }

    public function updateStatus(Delivery $delivery, DeliveryStatus $status, ?string $proofNotes, User $actor): Delivery
    {
        if ($delivery->status->isTerminal()) {
            throw new DeliveryException('This delivery is already in a final state and cannot be changed.');
        }

        if ($status === DeliveryStatus::COMPLETED && empty(trim((string) $proofNotes))) {
            throw new DeliveryException('Proof-of-delivery notes are required to mark a delivery as completed.');
        }

        return DB::transaction(function () use ($delivery, $status, $proofNotes, $actor) {
            $old = $delivery->status;

            $delivery->update([
                'status' => $status->value,
                'proof_notes' => $proofNotes ?? $delivery->proof_notes,
                'completed_at' => $status->isTerminal() ? now() : null,
            ]);

            AuditLogService::record(
                AuditAction::UPDATED,
                $delivery,
                ['status' => $old->value],
                ['status' => $status->value],
                $proofNotes,
                $delivery->location_id,
            );

            return $delivery->fresh();
        });
    }
}
