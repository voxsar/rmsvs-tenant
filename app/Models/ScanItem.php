<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class ScanItem extends Model
{
    use HasFactory;
    use UsesTenantConnection;

    public const TYPE_ACCESS = 'access';
    public const TYPE_MEAL = 'meal';
    public const TYPE_CONSUMABLE = 'consumable';

    public const PERIOD_ALWAYS = 'always';
    public const PERIOD_WEEKDAYS = 'weekdays';
    public const PERIOD_CUSTOM = 'custom';

    protected $fillable = [
        'name',
        'type',
        'description',
        'is_active',
        'active_period_type',
        'active_days',
        'active_start_time',
        'active_end_time',
        'custom_windows',
        'notify_if_missed',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'notify_if_missed' => 'boolean',
        'active_days' => 'array',
        'custom_windows' => 'array',
    ];

    protected $appends = [
        'active_period_summary',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_ACCESS => 'Access',
            self::TYPE_MEAL => 'Meals',
            self::TYPE_CONSUMABLE => 'Consumables',
        ];
    }

    public static function periodTypes(): array
    {
        return [
            self::PERIOD_ALWAYS => '24/7',
            self::PERIOD_WEEKDAYS => 'Weekdays',
            self::PERIOD_CUSTOM => 'Custom Windows',
        ];
    }

    /**
     * Accessor for a human-readable summary of the configured active period.
     */
    protected function activePeriodSummary(): Attribute
    {
        return Attribute::get(function (): string {
            return match ($this->active_period_type) {
                self::PERIOD_ALWAYS => '24/7',
                self::PERIOD_WEEKDAYS => $this->formatWeekdaySummary(),
                self::PERIOD_CUSTOM => $this->formatCustomSummary(),
                default => 'Unconfigured',
            };
        });
    }

    protected function formatWeekdaySummary(): string
    {
        $start = $this->active_start_time ? Carbon::parse($this->active_start_time)->format('g:i A') : '12:00 AM';
        $end = $this->active_end_time ? Carbon::parse($this->active_end_time)->format('g:i A') : '11:59 PM';

        return "Weekdays {$start} - {$end}";
    }

    protected function formatCustomSummary(): string
    {
        $windows = collect($this->custom_windows ?? [])->map(function (array $window) {
            $days = collect($window['days'] ?? [])->map(fn ($day) => ucfirst(strtolower($day)))->implode(', ');
            $start = isset($window['start']) ? Carbon::parse($window['start'])->format('g:i A') : 'N/A';
            $end = isset($window['end']) ? Carbon::parse($window['end'])->format('g:i A') : 'N/A';

            return trim($days ? "{$days}: {$start} - {$end}" : "{$start} - {$end}");
        })->filter()->implode('; ');

        return $windows !== '' ? $windows : 'Custom schedule';
    }

    public function shouldNotifyForMissedScan(): bool
    {
        return $this->type === self::TYPE_CONSUMABLE && $this->notify_if_missed;
    }

    public function scopeRequiringNotification($query)
    {
        return $query->where('type', self::TYPE_CONSUMABLE)
            ->where('notify_if_missed', true)
            ->where('is_active', true);
    }

    public function getActiveWindowsForDate(Carbon $date): Collection
    {
        return match ($this->active_period_type) {
            self::PERIOD_ALWAYS => collect([[
                'day' => strtoupper($date->format('l')),
                'start' => '00:00:00',
                'end' => '23:59:59',
            ]]),
            self::PERIOD_WEEKDAYS => $this->resolveWeekdayWindow($date),
            self::PERIOD_CUSTOM => $this->resolveCustomWindows($date),
            default => collect(),
        };
    }

    protected function resolveWeekdayWindow(Carbon $date): Collection
    {
        $weekdays = $this->active_days ?? ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'];
        $dayKey = strtoupper($date->format('l'));

        if (! in_array($dayKey, $weekdays, true)) {
            return collect();
        }

        return collect([[
            'day' => $dayKey,
            'start' => $this->active_start_time ?? '00:00:00',
            'end' => $this->active_end_time ?? '23:59:59',
        ]]);
    }

    protected function resolveCustomWindows(Carbon $date): Collection
    {
        $dayKey = strtoupper($date->format('l'));

        return collect($this->custom_windows ?? [])
            ->filter(function (array $window) use ($dayKey) {
                $days = collect($window['days'] ?? [])->map(fn ($day) => strtoupper($day));

                return $days->isEmpty() || $days->contains($dayKey);
            })
            ->map(function (array $window) use ($dayKey) {
                return [
                    'day' => $dayKey,
                    'start' => $window['start'] ?? '00:00:00',
                    'end' => $window['end'] ?? '23:59:59',
                ];
            });
    }

    public function toNotificationPayload(?Carbon $date = null): array
    {
        $date ??= Carbon::now();
        $windows = $this->getActiveWindowsForDate($date);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'active_period_type' => $this->active_period_type,
            'active_windows' => $windows->values()->all(),
            'notify_if_missed' => $this->shouldNotifyForMissedScan(),
            'is_active' => $this->is_active,
        ];
    }
}
