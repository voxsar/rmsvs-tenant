<?php

namespace App\Filament\Resources\Tenant\MealRecordResource\Pages;

use App\Filament\Resources\Tenant\MealRecordResource;
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
