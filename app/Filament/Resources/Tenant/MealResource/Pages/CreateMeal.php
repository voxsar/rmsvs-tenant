<?php

namespace App\Filament\Resources\Tenant\MealResource\Pages;

use App\Filament\Resources\Tenant\MealResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMeal extends CreateRecord
{
    protected static string $resource = MealResource::class;
}
