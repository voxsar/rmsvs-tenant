<?php

namespace App\Filament\Resources\Tenant\GuestResource\Pages;

use App\Filament\Resources\Tenant\GuestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListGuests extends ListRecords
{
    protected static string $resource = GuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('create guest')),
        ];
    }
    
    public function mount(): void
    {
        abort_unless(
            Auth::guard('tenant')->check() && 
            Auth::guard('tenant')->user()->can('view guest'), 
            403
        );
        
        parent::mount();
    }

	
    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->badge("Active")
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', 'active')),
            'inactive' => Tab::make('InActive')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', 'inactive')),
		];
	}
}
