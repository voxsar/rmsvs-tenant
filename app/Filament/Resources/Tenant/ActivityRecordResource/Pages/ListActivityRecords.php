<?php

namespace App\Filament\Resources\Tenant\ActivityRecordResource\Pages;

use App\Filament\Resources\Tenant\ActivityRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityRecords extends ListRecords
{
    protected static string $resource = ActivityRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [ ];
    }
}
