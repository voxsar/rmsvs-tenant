<?php

namespace App\Filament\Pages\Tenant;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\ResidentStatsOverview;
use App\Filament\Widgets\ResidentTypeChart;
use App\Filament\Widgets\AgeDistributionChart;
use App\Filament\Widgets\GenderDistributionChart;
use App\Filament\Widgets\TopNationalitiesChart;
use App\Filament\Widgets\OccupancyTrendChart;
use App\Filament\Widgets\BirthdaysWidget;
use App\Filament\Widgets\DailyReportWidget;
use App\Filament\Widgets\AbsenceTrackerWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    //protected static string $view = 'filament.pages.dashboard';
    
    public function getTitle(): string 
    {
        return 'Resident Statistics Dashboard';
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            ResidentStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
    
    /**
     * Register all the custom widgets for the dashboard
     */
    public function getWidgets(): array
    {
        return [
            ResidentStatsOverview::class,
            ResidentTypeChart::class,
            AgeDistributionChart::class,
            GenderDistributionChart::class,
            TopNationalitiesChart::class,
            OccupancyTrendChart::class,
            BirthdaysWidget::class,
            AbsenceTrackerWidget::class,
        ];
    }
}