<?php

namespace App\Filament\Resources\Tenant\ConsumableResource\Pages;

use App\Filament\Resources\Tenant\ConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewConsumable extends ViewRecord
{
    protected static string $resource = ConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
