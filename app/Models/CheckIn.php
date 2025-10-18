<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class CheckIn extends Pivot
{
    use HasFactory;
    use UsesTenantConnection;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'check_ins';

    protected $fillable = [
        'guest_id',
        'room_id',
        'activity_type',
        'date_of_arrival',
        'date_of_departure',
        'qr_code',
    ];

    protected $casts = [
        'date_of_arrival' => 'datetime',
        'date_of_departure' => 'datetime',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($checkIn) {
            $checkIn->ensureGuestRoomRelation();

            // If this is a check-out (date_of_arrival is set), record an absence
            if ($checkIn->date_of_arrival && $checkIn->guest && $checkIn->guest->type === 'RESIDENT') {
                $checkIn->guest->recordAbsence($checkIn);
            }

            // If this is a check-in (date_of_departure is set), complete any active absences
            if ($checkIn->date_of_departure && $checkIn->guest && $checkIn->guest->type === 'RESIDENT') {
                $checkIn->guest->completeAbsences($checkIn);
            }
        });

        static::updated(function ($checkIn) {
            if ($checkIn->isDirty('room_id')) {
                $checkIn->ensureGuestRoomRelation();
            }

            // If date_of_arrival was added/changed, record an absence
            if ($checkIn->isDirty('date_of_arrival') && $checkIn->date_of_arrival &&
                $checkIn->guest && $checkIn->guest->type === 'RESIDENT') {
                $checkIn->guest->recordAbsence($checkIn);
            }

            // If date_of_departure was added/changed, complete any active absences
            if ($checkIn->isDirty('date_of_departure') && $checkIn->date_of_departure &&
                $checkIn->guest && $checkIn->guest->type === 'RESIDENT') {
                $checkIn->guest->completeAbsences($checkIn);
            }
        });
    }

    // Ensure both guest and room have a relationship in the guest_room table
    public function ensureGuestRoomRelation()
    {
        if ($this->room && $this->guest) {
            // Always regenerate QR code to ensure it's up to date
            $this->room->generateGuestRoomQrCode($this->guest);
        }
    }

    public function consumables(): BelongsToMany
    {
        // Use the direct table name instead of a dynamic reference to prevent infinite loops
        return $this->belongsToMany(Consumable::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function mealRecords(): HasMany
    {
        return $this->hasMany(Meal::class);
    }
}
