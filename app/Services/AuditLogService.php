<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public static function record(
        AuditAction $action,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?int $locationId = null,
    ): AuditLog {
        $request = request();
        $user = $request?->user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action->value,
            'model' => class_basename($model),
            'model_id' => $model->getKey(),
            'location_id' => $locationId ?? $model->location_id ?? $user?->location_id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
