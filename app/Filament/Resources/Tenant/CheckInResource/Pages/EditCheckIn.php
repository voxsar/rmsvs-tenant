<?php

namespace App\Filament\Resources\Tenant\CheckInResource\Pages;

use App\Filament\Resources\Tenant\CheckInResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCheckIn extends EditRecord
{
    protected static string $resource = CheckInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
