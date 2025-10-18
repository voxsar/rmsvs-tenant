<?php

namespace App\Filament\Resources\Tenant\CheckInResource\Pages;

use App\Filament\Resources\Tenant\CheckInResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCheckIns extends ListRecords
{
    protected static string $resource = CheckInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Standard create action
            Actions\CreateAction::make()
                ->disabled(fn () => ! Auth::guard('tenant')->user()->can('create check-in'))
            //     ->tooltip(fn (bool $disabled) => $disabled
            //       ? 'You don\'t have permission to create check-ins'
            // /     : 'Create a new check-in'),
            ,
            // Multi-guest check-in action
            Actions\Action::make('multiGuest')
                ->label('Multi-Guest Check-In')
                ->url(route('filament.admin.resources.tenant.check-ins.multi-guest'))
                ->disabled(fn () => ! Auth::guard('tenant')->user()->can('create check-in')),
            // Generate QR codes for all check-ins missing them
            Actions\Action::make('generateAllQrCodes')
                ->label('Generate All QR Codes')
                ->icon('heroicon-o-qr-code')
                ->action(function () {
                    $checkIns = \App\Models\CheckIn::all();
                    $generated = 0;
                    
                    foreach ($checkIns as $checkIn) {
                        if ($checkIn->guest && $checkIn->room && !$checkIn->qr_code) {
                            // Ensure guest-room relationship exists
                            $exists = $checkIn->guest->rooms()->where('room_id', $checkIn->room->id)->exists();
                            if (!$exists) {
                                $checkIn->guest->rooms()->attach($checkIn->room->id);
                            }
                            
                            // Generate QR code
                            $qrCode = $checkIn->room->generateGuestRoomQrCode($checkIn->guest);
                            if ($qrCode) {
                                $generated++;
                            }
                        }
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title('QR Codes Generated')
                        ->body("Successfully generated {$generated} QR codes for check-ins without them")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Generate All Missing QR Codes')
                ->modalDescription('This will generate QR codes for all check-ins that don\'t have them yet.')
                ->modalSubmitActionLabel('Generate All')
                ->color('warning'),
            // ->tooltip(fn (bool $disabled) => $disabled
            //   ? 'You don\'t have permission to create check-ins'
            // : 'Create multiple check-ins at once'),
        ];
    }

    // Ensure users can at least view the list
    public function mount(): void
    {
        abort_unless(
            Auth::guard('tenant')->check() &&
            Auth::guard('tenant')->user()->can('view check-in'),
            403
        );

        parent::mount();
    }
}
