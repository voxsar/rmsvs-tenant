<?php

namespace App\Filament\Resources\Tenant\CustomRequestResource\Pages;

use App\Filament\Resources\Tenant\CustomRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomRequests extends ListRecords
{
    protected static string $resource = CustomRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
