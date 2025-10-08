<?php

namespace App\Filament\Resources\CheckInResource\Pages;

use App\Filament\Resources\CheckInResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCheckIns extends ListRecords
{
    protected static string $resource = CheckInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('multiGuestCheckIn')
                ->label('Multi-Guest Check-In')
                ->icon('heroicon-o-user-group')
                ->url(static::getResource()::getUrl('multi-guest'))
                ->color('success'),
        ];
    }
}
