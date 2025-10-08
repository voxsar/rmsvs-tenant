<?php

namespace App\Filament\Resources\Tenant\RoleResource\Pages;

use App\Filament\Resources\Tenant\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
