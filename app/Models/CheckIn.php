<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckIn extends Pivot
{
    use HasFactory;
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
        'date_of_arrival',
        'date_of_departure',
		'qr_code'
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
        });

        static::updated(function ($checkIn) {
            if ($checkIn->isDirty('room_id')) {
                $checkIn->ensureGuestRoomRelation();
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
