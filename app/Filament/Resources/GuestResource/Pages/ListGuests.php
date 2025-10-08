<?php

namespace App\Filament\Resources\GuestResource\Pages;

use App\Filament\Resources\GuestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Enums\FiltersLayout;

class ListGuests extends ListRecords
{
    protected static string $resource = GuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableFiltersLayout(): ?string
    {
        return FiltersLayout::Tabs;
    }
}
