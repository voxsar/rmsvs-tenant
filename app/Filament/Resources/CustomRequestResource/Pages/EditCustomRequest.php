<?php

namespace App\Filament\Resources\CustomRequestResource\Pages;

use App\Filament\Resources\CustomRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomRequest extends EditRecord
{
    protected static string $resource = CustomRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
