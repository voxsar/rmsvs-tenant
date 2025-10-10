<?php

namespace App\Filament\Resources\Tenant\CheckInResource\Pages;

use App\Filament\Resources\Tenant\CheckInResource;
use Filament\Infolists\Components\Actions\Action as Act;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;

class ViewCheckIn extends ViewRecord
{
    protected static string $resource = CheckInResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Check-In Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('guest.first_name')
                            ->label('Guest Name')
                            ->formatStateUsing(fn ($record) => "{$record->guest->first_name} {$record->guest->last_name}"),
                        Infolists\Components\TextEntry::make('date_of_arrival')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('date_of_departure')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('room.room_no')
                            ->label('Room')
                            ->formatStateUsing(fn ($record) => "{$record->room->room_no} ({$record->room->building}, Floor {$record->room->floor})"),
                    ])->columns(2),
                
                Infolists\Components\Section::make('QR Code')
                    ->schema([
                        Infolists\Components\ImageEntry::make('qr_code')
                            ->label('Room Access QR Code')
                            ->visibility('private')
                            ->size(300)
                            ->disk('public')
                            ->action(
                                Act::make('viewQrCode')
                                    ->label('View QR Code')
                                    ->modalHeading('Room Access QR Code')
                                    ->modalDescription(fn ($record) => "QR Code for {$record->guest->first_name} {$record->guest->last_name}, Room {$record->room->room_no}")
                                    ->modalContent(fn ($record) => view('filament.resources.qr-code-modal', ['record' => $record]))
                                    ->modalSubmitAction(false)
                                    ->button()
                                    ->visible(fn ($record) => $record->qr_code !== null)
                            )
                            ->hidden(fn ($record) => $record->qr_code === null),
                    ])
                    ->hidden(fn ($record) => $record->qr_code === null),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewQrCode')
                ->label('View QR Code')
                ->icon('heroicon-o-qr-code')
                ->modalHeading('Room Access QR Code')
                ->modalDescription(fn ($record) => "QR Code for {$record->guest->first_name} {$record->guest->last_name}, Room {$record->room->room_no}")
                ->modalContent(fn ($record) => view('filament.resources.qr-code-modal', ['record' => $record]))
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->visible(fn ($record) => $record->qr_code !== null)
                ->color('success'),
        ];
    }
}