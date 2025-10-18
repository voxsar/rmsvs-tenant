<?php

namespace App\Filament\Resources\Tenant\TransitResource\Pages;

use App\Filament\Resources\Tenant\TransitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EditTransit extends EditRecord
{
    protected static string $resource = TransitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('delete transit')),
        ];
    }

    protected function authorizeAccess(): void
    {
        abort_unless(
            Auth::guard('tenant')->check() &&
            Gate::forUser(Auth::guard('tenant')->user())->check('update transit'),
            403
        );
    }
}
