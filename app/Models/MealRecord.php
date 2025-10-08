<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealRecord extends Model
{
    use HasFactory;

	protected $fillable = [
        'guest_id',
        'room_id',
        'meal_id',
		'date_of_transit',
        'transit_type',
    ];

    protected $casts = [
        'date_of_transit' => 'datetime',
    ];

    /**
     * The possible meal types.
     */
    public const TRANSIT_TYPES = [
		'CHECK_IN' => 'Check In',
		'CHECK_OUT' => 'Check Out',
    ];

    public function checkIn(): BelongsTo
    {
        return $this->belongsTo(CheckIn::class);
    }
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }
}
