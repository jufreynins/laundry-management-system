<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'zip',
        'phone',
        'timezone',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function businessSettings(): HasMany
    {
        return $this->hasMany(BusinessSettings::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function setting(string $key): ?string
    {
        return $this->businessSettings()->where('key', $key)->value('value');
    }

    public function setSetting(string $key, string $value, bool $encrypted = false): void
    {
        $this->businessSettings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'encrypted' => $encrypted]
        );
    }
}
