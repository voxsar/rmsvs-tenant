<?php

namespace App\Filament\Resources\Tenant\ScannerResource\Pages;

use App\Filament\Resources\Tenant\ScannerResource;
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
