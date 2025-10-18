<?php

namespace App\Models;

use App\Services\CustomRequestService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class CustomRequest extends Pivot
{
    use HasFactory;
    use UsesTenantConnection;

    public $incrementing = true;

    protected $table = 'custom_requests';

    protected $fillable = [
        'guest_id',
        'room_id',
        'consumable_id',
        'request_type',
        'activity_type',
        'description',
        'status',
        'response_msg',
        'responded_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Request types available in the system
     */
    public const REQUEST_TYPES = [
        'LATE_DINNER' => 'Late Dinner',
        'ABSENCE' => 'Absence',
        'CONSUMABLE' => 'Consumable',
        'OTHER' => 'Other',
    ];

    /**
     * Request statuses available in the system
     */
    public const REQUEST_STATUSES = [
        'PENDING' => 'Pending',
        'APPROVED' => 'Approved',
        'DENIED' => 'Denied',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Process the request based on its type after creation
        static::created(function ($request) {
            $service = new CustomRequestService;
            $service->processRequest($request);
        });

        // Process the request based on its type after update if request_type changed
        static::updated(function ($request) {
            if ($request->isDirty('request_type')) {
                $service = new CustomRequestService;
                $service->processRequest($request);
            }
        });
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
    }
}
