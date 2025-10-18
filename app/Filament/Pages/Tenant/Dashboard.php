<?php

namespace App\Filament\Pages\Tenant;

use App\Filament\Widgets\AbsenceTrackerWidget;
use App\Filament\Widgets\EmergencyRollCallWidget;
use App\Filament\Widgets\LastShiftReportWidget;
use App\Filament\Widgets\MissedConsumablesWidget;
use App\Filament\Widgets\MissedMealsWidget;
use App\Filament\Widgets\PersonsOnSiteWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    // protected static string $view = 'filament.pages.dashboard';

    public function getTitle(): string
    {
        return 'Resident Statistics Dashboard';
    }

    public function getWidgets(): array
    {
        return [
            PersonsOnSiteWidget::class,
            LastShiftReportWidget::class,
            EmergencyRollCallWidget::class,
            AbsenceTrackerWidget::class,
            MissedMealsWidget::class,
            MissedConsumablesWidget::class,
        ];
    }
}
