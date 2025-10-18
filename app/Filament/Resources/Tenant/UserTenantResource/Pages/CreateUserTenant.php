<?php

namespace App\Filament\Resources\Tenant\UserTenantResource\Pages;

use App\Filament\Resources\Tenant\UserTenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserTenant extends CreateRecord
{
    protected static string $resource = UserTenantResource::class;
}
