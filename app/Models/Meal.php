<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Meal extends Model
{
    use HasFactory;
    use UsesTenantConnection;

    protected $fillable = [
        'range_start',
        'range_end',
        'week_day',
        'meal_type',
    ];

    protected $casts = [
        'range_start' => 'datetime',
        'range_end' => 'datetime',
        'week_day' => 'array',
    ];

    /**
     * The possible meal types.
     */
    public const MEAL_TYPES = [
        'BREAKFAST' => 'Breakfast',
        'LUNCH' => 'Lunch',
        'DINNER' => 'Dinner',
    ];

    /**
     * The possible meal types.
     */
    public const WEEK_DAYS = [
        'MONDAY' => 'Monday',
        'TUESDAY' => 'Tuesday',
        'WEDNESDAY' => 'Wednesday',
        'THURSDAY' => 'Thursday',
        'FRIDAY' => 'Friday',
        'SATURDAY' => 'Saturday',
        'SUNDAY' => 'Sunday',
    ];

    public function checkIn(): BelongsTo
    {
        return $this->belongsTo(CheckIn::class);
    }
}
