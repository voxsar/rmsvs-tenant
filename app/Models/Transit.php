<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transit extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'date_of_transit',
        'room_id',
		'transit_type',
    ];

    protected $casts = [
        'date_of_transit' => 'datetime',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
    
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Request statuses available in the system
     */
    public const TRANSIT_TYPES = [
        'CHECKIN' => 'Check In',
		'CHECKOUT' => 'Check Out',
		'CHECKINOUT' => 'Check In/Out',
    ];
}
