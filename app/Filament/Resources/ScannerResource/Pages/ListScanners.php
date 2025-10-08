<?php

namespace App\Filament\Resources\ScannerResource\Pages;

use App\Filament\Resources\ScannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScanners extends ListRecords
{
    protected static string $resource = ScannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
