<?php

namespace App\Filament\Resources\TransitResource\Pages;

use App\Filament\Resources\TransitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransits extends ListRecords
{
    protected static string $resource = TransitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
