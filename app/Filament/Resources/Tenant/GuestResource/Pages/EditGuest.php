<?php

namespace App\Filament\Resources\Tenant\GuestResource\Pages;

use App\Filament\Resources\Tenant\GuestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EditGuest extends EditRecord
{
    protected static string $resource = GuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('view guest')),
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('delete guest')),
        ];
    }

    protected function authorizeAccess(): void
    {
        abort_unless(
            Auth::guard('tenant')->check() && 
            Auth::guard('tenant')->user()->can('update guest'),
            403
        );
    }
}
