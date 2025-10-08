<?php

namespace App\Filament\Resources\CustomRequestResource\Pages;

use App\Filament\Resources\CustomRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomRequest extends ViewRecord
{
    protected static string $resource = CustomRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}