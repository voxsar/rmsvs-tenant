<?php

namespace App\Filament\Resources\Tenant\GuestResource\Pages;

use App\Filament\Resources\Tenant\GuestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditGuest extends EditRecord
{
    protected static string $resource = GuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewQrCode')
                ->label('View QR Code')
                ->icon('heroicon-o-qr-code')
                ->modalHeading('Guest QR Code')
                ->modalDescription(fn () => "QR Code for {$this->record->first_name} {$this->record->last_name}")
                ->modalContent(fn () => view('filament.resources.guest-qr-code-modal', ['guest' => $this->record]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->visible(fn () => filled($this->record->qr_code)),
            Actions\Action::make('regenerateQrCode')
                ->label('Regenerate QR Code')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    $this->record->generateQrCode();
                    \Filament\Notifications\Notification::make()
                        ->title('QR Code Regenerated')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Regenerate QR Code')
                ->modalDescription('This will generate a new QR code for this guest. The old QR code will be replaced.')
                ->modalSubmitActionLabel('Regenerate'),
            Actions\ViewAction::make()
                ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('view guest')),
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('delete guest')),
        ];
    }

    protected function authorizeAccess(): void
    {
        abort_unless(
            Auth::guard('tenant')->check() &&
            Auth::guard('tenant')->user()->can('update guest'),
            403
        );
    }
}
