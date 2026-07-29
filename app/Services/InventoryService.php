<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\InventoryTransactionType;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function recordTransaction(
        InventoryItem $item,
        InventoryTransactionType $type,
        string $quantity,
        ?string $reason,
        User $actor
    ): InventoryTransaction {
        if ($type !== InventoryTransactionType::ADJUSTMENT && bccomp($quantity, '0.00', 2) <= 0) {
            throw new InventoryException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($item, $type, $quantity, $reason, $actor) {
            $item = InventoryItem::where('id', $item->id)->lockForUpdate()->first();

            $delta = match ($type) {
                InventoryTransactionType::RECEIVED => $quantity,
                InventoryTransactionType::USED, InventoryTransactionType::WASTE => bcmul($quantity, '-1', 2),
                InventoryTransactionType::ADJUSTMENT => $quantity,
            };

            $newQuantity = bcadd((string) $item->current_quantity, $delta, 2);

            if (bccomp($newQuantity, '0.00', 2) < 0) {
                throw new InventoryException('This transaction would result in negative stock.');
            }

            $previousQuantity = (string) $item->current_quantity;
            $item->update(['current_quantity' => $newQuantity]);

            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'type' => $type->value,
                'quantity' => $quantity,
                'quantity_after' => $newQuantity,
                'reason' => $reason,
                'recorded_by' => $actor->id,
                'created_at' => now(),
            ]);

            AuditLogService::record(
                AuditAction::UPDATED,
                $item,
                ['current_quantity' => $previousQuantity],
                ['current_quantity' => $newQuantity],
                $reason ?? $type->label(),
                $item->location_id,
            );

            return $transaction;
        });
    }
}
