<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'location_id',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function canAccessLocation(Location $location): bool
    {
        if ($this->role === UserRole::OWNER) {
            return true;
        }
        return $this->location_id === $location->id;
    }

    /**
     * The location_id to scope list/report queries by: null means unrestricted
     * (Owner, always; any other role only if explicitly assigned no single location).
     * Managers are NOT automatically unrestricted — only Owner bypasses location
     * scoping by role alone, matching canAccessLocation()'s per-record check.
     */
    public function scopedLocationId(): ?int
    {
        return $this->role === UserRole::OWNER ? null : $this->location_id;
    }
}
