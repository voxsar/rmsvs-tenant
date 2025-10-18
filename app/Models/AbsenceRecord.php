<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class AbsenceRecord extends Model
{
    use HasFactory;
    use UsesTenantConnection;

    protected $fillable = [
        'guest_id',
        'start_date',
        'end_date',
        'is_authorized',
        'notes',
        'status',
        'duration_hours',
        'check_in_id',
        'check_out_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_authorized' => 'boolean',
    ];

    /**
     * Get the guest associated with this absence record
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Get the check-in record that marks the start of this absence
     */
    public function checkIn(): BelongsTo
    {
        return $this->belongsTo(CheckIn::class, 'check_in_id');
    }

    /**
     * Get the check-in record that marks the end of this absence
     */
    public function checkOut(): BelongsTo
    {
        return $this->belongsTo(CheckIn::class, 'check_out_id');
    }

    /**
     * Calculate the duration of the absence in hours
     */
    public function calculateDuration(): ?int
    {
        if (! $this->start_date) {
            return null;
        }

        $endDate = $this->end_date ?? Carbon::now();

        return $this->start_date->diffInHours($endDate);
    }

    /**
     * Update the duration hours field
     */
    public function updateDuration(): void
    {
        $this->duration_hours = $this->calculateDuration();
        $this->save();
    }

    /**
     * Complete an absence record when a guest returns
     */
    public function completeAbsence(?Carbon $endDate = null): void
    {
        $this->end_date = $endDate ?? Carbon::now();
        $this->status = 'completed';
        $this->updateDuration();
        $this->save();
    }

    /**
     * Get active absence records
     */
    public static function active()
    {
        return self::where('status', 'active')->whereNull('end_date');
    }

    /**
     * Get absence records that have lasted longer than the given hours
     */
    public static function longerThan(int $hours)
    {
        return self::where(function ($query) use ($hours) {
            // For completed absences, check duration_hours
            $query->where('status', 'completed')
                ->where('duration_hours', '>=', $hours);
        })->orWhere(function ($query) use ($hours) {
            // For active absences, calculate based on start_date
            $query->where('status', 'active')
                ->whereNull('end_date')
                ->where('start_date', '<=', Carbon::now()->subHours($hours));
        });
    }

    /**
     * Find or create an absence record for a guest when they check out
     */
    public static function recordAbsence(Guest $guest, CheckIn $checkIn): self
    {
        // Create a new absence record
        return self::create([
            'guest_id' => $guest->id,
            'start_date' => $checkIn->date_of_arrival,
            'is_authorized' => (bool) $guest->authorized_absence,
            'status' => 'active',
            'check_in_id' => $checkIn->id,
        ]);
    }
}
