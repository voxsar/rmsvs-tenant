<?php

namespace App\Filament\Resources\Tenant\ConsumableResource\Pages;

use App\Filament\Resources\Tenant\ConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsumables extends ListRecords
{
    protected static string $resource = ConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
