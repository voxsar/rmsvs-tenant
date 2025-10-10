<?php

namespace App\Filament\Resources\Tenant\TransitResource\Pages;

use App\Filament\Resources\Tenant\TransitResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CreateTransit extends CreateRecord
{
    protected static string $resource = TransitResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(
            Auth::guard('tenant')->check() && 
            Gate::forUser(Auth::guard('tenant')->user())->check('create transit'),
            403
        );
    }
}
