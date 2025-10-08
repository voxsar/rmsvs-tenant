<?php

namespace App\Filament\Resources\ScannerResource\Pages;

use App\Filament\Resources\ScannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScanner extends EditRecord
{
    protected static string $resource = ScannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
