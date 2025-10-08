<?php

namespace App\Filament\Resources\TransitResource\Pages;

use App\Filament\Resources\TransitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransit extends EditRecord
{
    protected static string $resource = TransitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
