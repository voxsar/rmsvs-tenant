<?php

namespace App\Filament\Resources\MealRecordResource\Pages;

use App\Filament\Resources\MealRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMealRecords extends ListRecords
{
    protected static string $resource = MealRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
