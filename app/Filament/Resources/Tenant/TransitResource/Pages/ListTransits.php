<?php

namespace App\Filament\Resources\Tenant\TransitResource\Pages;

use App\Filament\Resources\Tenant\TransitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ListTransits extends ListRecords
{
    protected static string $resource = TransitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('create transit')),
        ];
    }
    
    public function mount(): void
    {
        abort_unless(
            Auth::guard('tenant')->check() && 
            Gate::forUser(Auth::guard('tenant')->user())->check('view transit'), 
            403
        );
        
        parent::mount();
    }
}
