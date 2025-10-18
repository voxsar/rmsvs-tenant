<?php

namespace App\Filament\Resources\Tenant\CheckInResource\Pages;

use App\Filament\Resources\Tenant\CheckInResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCheckIns extends ListRecords
{
    protected static string $resource = CheckInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Standard create action
            Actions\CreateAction::make()
                ->disabled(fn () => ! Auth::guard('tenant')->user()->can('create check-in'))
            //     ->tooltip(fn (bool $disabled) => $disabled
            //       ? 'You don\'t have permission to create check-ins'
            // /     : 'Create a new check-in'),
            ,
            // Multi-guest check-in action
            Actions\Action::make('multiGuest')
                ->label('Multi-Guest Check-In')
                ->url(route('filament.admin.resources.tenant.check-ins.multi-guest'))
                ->disabled(fn () => ! Auth::guard('tenant')->user()->can('create check-in')),
            // ->tooltip(fn (bool $disabled) => $disabled
            //   ? 'You don\'t have permission to create check-ins'
            // : 'Create multiple check-ins at once'),
        ];
    }

    // Ensure users can at least view the list
    public function mount(): void
    {
        abort_unless(
            Auth::guard('tenant')->check() &&
            Auth::guard('tenant')->user()->can('view check-in'),
            403
        );

        parent::mount();
    }
}
