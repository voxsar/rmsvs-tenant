<?php

namespace App\Filament\Widgets;

use App\Models\AbsenceRecord;
use App\Models\CheckIn;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PersonsOnSiteWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'xl' => 2,
    ];

    protected function getStats(): array
    {
        $now = Carbon::now();

        $activeCheckInsQuery = CheckIn::query()
            ->where('date_of_arrival', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('date_of_departure')
                    ->orWhere('date_of_departure', '>', $now);
            });

        $totalOnSite = (clone $activeCheckInsQuery)->count();
        $residentsOnSite = (clone $activeCheckInsQuery)
            ->whereHas('guest', fn ($query) => $query->where('type', 'RESIDENT'))
            ->count();
        $staffOnSite = (clone $activeCheckInsQuery)
            ->whereHas('guest', fn ($query) => $query->where('type', 'STAFF'))
            ->count();
        $visitorsOnSite = (clone $activeCheckInsQuery)
            ->whereHas('guest', fn ($query) => $query->where('type', 'VISITORS'))
            ->count();

        $authorizedAbsences = AbsenceRecord::active()
            ->where('is_authorized', true)
            ->count();
        $unauthorizedAbsences = AbsenceRecord::active()
            ->where('is_authorized', false)
            ->count();

        return [
            Stat::make('People On Site', number_format($totalOnSite))
                ->description('Active check-ins right now')
                ->icon('heroicon-o-users'),
            Stat::make('Residents On Site', number_format($residentsOnSite))
                ->description('Residents currently checked in')
                ->icon('heroicon-o-home'),
            Stat::make('Staff On Site', number_format($staffOnSite))
                ->description('Staff members inside the centre')
                ->icon('heroicon-o-briefcase'),
            Stat::make('Visitors On Site', number_format($visitorsOnSite))
                ->description('Registered visitors present today')
                ->icon('heroicon-o-user-group'),
            Stat::make('Unauthorized Absences', number_format($unauthorizedAbsences))
                ->description($authorizedAbsences.' authorised')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($unauthorizedAbsences > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }
}
