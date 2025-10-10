<?php

namespace App\Filament\Resources\Tenant\MealResource\Pages;

use App\Filament\Resources\Tenant\MealResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeal extends EditRecord
{
    protected static string $resource = MealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
