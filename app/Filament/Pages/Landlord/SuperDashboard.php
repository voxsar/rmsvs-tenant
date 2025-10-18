<?php

namespace App\Filament\Pages\Landlord;

use Filament\Pages\Dashboard as BaseDashboard;

class SuperDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    // protected static string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Landlord Dashboard';

    public static function getRouteName(?string $panel = null): string
    {
        return 'filament.super.pages.super-dashboard';
    }

    public function getTitle(): string
    {
        return 'Resident Statistics Dashboard';
    }

    protected function getHeaderWidgets(): array
    {
        return [
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
        ];
    }
}
