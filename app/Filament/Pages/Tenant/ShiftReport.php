<?php

namespace App\Filament\Pages\Tenant;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ShiftReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?string $navigationLabel = 'Shift Reports';

    protected static ?string $navigationGroup = 'Property';

    protected static ?string $title = 'Shift Reports';

    protected static ?string $slug = 'shift-reports';

    protected static string $view = 'filament.pages.tenant.shift-report';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        return Auth::guard('tenant')->user()->can('view daily-report');
    }

    public function getHeading(): string
    {
        return 'Daily Shift Report';
    }

}
