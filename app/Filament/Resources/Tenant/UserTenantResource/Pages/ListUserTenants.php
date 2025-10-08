<?php

namespace App\Filament\Resources\Tenant\UserTenantResource\Pages;

use App\Filament\Resources\Tenant\UserTenantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserTenants extends ListRecords
{
    protected static string $resource = UserTenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
