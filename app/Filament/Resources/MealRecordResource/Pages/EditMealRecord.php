<?php

namespace App\Filament\Resources\MealRecordResource\Pages;

use App\Filament\Resources\MealRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMealRecord extends EditRecord
{
    protected static string $resource = MealRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
