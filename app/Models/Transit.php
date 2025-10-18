<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Transit extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'guest_id',
        'room_id',
        'date_of_transit',
        'transit_type',
        'processed_at',
        'processed_by',
        'notes',
    ];

    protected $casts = [
        'date_of_transit' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Transit types
    const TRANSIT_TYPES = [
        'CHECKIN' => 'Check In',
        'CHECKOUT' => 'Check Out',
        'CHECKINOUT' => 'Access',
    ];

    // Relationships
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(UserTenant::class, 'processed_by');
    }

    // Permission-based scopes
    public function scopeViewableBy($query, $user)
    {
        if (! $user->can('view transit')) {
            return $query->whereRaw('1 = 0'); // Returns nothing if no permission
        }

        return $query;
    }

    // Helper methods for permission checks
    public function canBeProcessedBy($user): bool
    {
        if ($this->processed_at) {
            return false; // Already processed
        }

        if ($this->transit_type === 'CHECKIN' && $user->can('process check-ins')) {
            return true;
        }

        if ($this->transit_type === 'CHECKOUT' && $user->can('process check-outs')) {
            return true;
        }

        return false;
    }

    public function canBeUpdatedBy($user): bool
    {
        return $user->can('update transit');
    }

    public function canBeDeletedBy($user): bool
    {
        return $user->can('delete transit');
    }
}
