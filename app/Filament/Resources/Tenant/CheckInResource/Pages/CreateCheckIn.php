<?php

namespace App\Filament\Resources\Tenant\CheckInResource\Pages;

use App\Filament\Resources\Tenant\CheckInResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCheckIn extends CreateRecord
{
    protected static string $resource = CheckInResource::class;
}
