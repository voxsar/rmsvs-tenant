<?php

namespace App\Filament\Resources\CustomRequestResource\Pages;

use App\Filament\Resources\CustomRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomRequest extends CreateRecord
{
    protected static string $resource = CustomRequestResource::class;
}
