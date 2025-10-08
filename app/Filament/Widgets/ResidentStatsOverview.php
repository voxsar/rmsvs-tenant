<?php

namespace App\Filament\Widgets;

use App\Models\Guest;
use App\Models\CheckIn;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ResidentStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Get current and previous month data for comparison
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();
        
        // Total Residents
        $totalResidents = Guest::count();
        $lastMonthResidents = Guest::where('created_at', '<', $lastMonth)->count();
        $residentChange = $totalResidents - $lastMonthResidents;
        
        // Active Residents
        $activeResidents = Guest::where('is_active', 'active')->count();
        $activePercentage = $totalResidents > 0 ? round(($activeResidents / $totalResidents) * 100) : 0;
        
        // Currently Checked-In
        $checkedInResidents = CheckIn::whereNull('date_of_departure')->count();
        $checkedInPercentage = $activeResidents > 0 ? round(($checkedInResidents / $activeResidents) * 100) : 0;
        
        // Average Stay Duration
        $avgStayDuration = CheckIn::whereNotNull('date_of_departure')
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->selectRaw('AVG(DATEDIFF(date_of_departure, created_at)) as avg_duration')
            ->first()->avg_duration ?? 0;
        
        $lastMonthAvgStay = CheckIn::whereNotNull('date_of_departure')
            ->whereBetween('created_at', [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()])
            ->selectRaw('AVG(DATEDIFF(date_of_departure, created_at)) as avg_duration')
            ->first()->avg_duration ?? 0;
        
        $stayDurationChange = round($avgStayDuration - $lastMonthAvgStay);
        
        return [
            Stat::make('Total Residents', $totalResidents)
                ->description($residentChange >= 0 ? "+{$residentChange} from last month" : "{$residentChange} from last month")
                ->descriptionIcon($residentChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($residentChange >= 0 ? 'success' : 'danger'),
            
            Stat::make('Active Residents', $activeResidents)
                ->description("{$activePercentage}% of total")
                ->color('info'),
                
            Stat::make('Currently Checked-In', $checkedInResidents)
                ->description("{$checkedInPercentage}% of active")
                ->color('info'),
                
            Stat::make('Average Stay Duration', round($avgStayDuration) . ' days')
                ->description($stayDurationChange >= 0 ? "+{$stayDurationChange} days from last month" : "{$stayDurationChange} days from last month")
                ->descriptionIcon($stayDurationChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($stayDurationChange >= 0 ? 'success' : 'danger'),
        ];
    }
}