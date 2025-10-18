<?php

namespace App\Filament\Pages;

use App\Models\CheckIn;
use App\Models\Guest;
use App\Models\Room;
use Carbon\Carbon;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ManualScanPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Manual Scan';

    protected static ?string $title = 'Manual Scan';

    protected static ?string $slug = 'manual-scans';

    protected static ?string $navigationGroup = 'Scans';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    // This will store the selected guest's most recent check-in data
    public $guestRoomId = null;

    public $guestRoomNo = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::guard('tenant')->check();
    }

    protected static string $view = 'filament.pages.manual-scan-page';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        Select::make('guest_id')
                            ->label('Resident')
                            ->options(Guest::query()
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(function (Guest $guest) {
                                    $roomInfo = $guest->currentRoomNo() !== 'N/A'
                                        ? ' - Room: '.$guest->currentRoomNo()
                                        : '';

                                    return [$guest->id => "{$guest->first_name} {$guest->last_name}{$roomInfo}"];
                                }))
                            ->searchable(['first_name', 'last_name', 'email', 'phone', 'TRN'])
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $guest = Guest::find($state);

                                    // Get the latest active check-in for this guest
                                    $latestCheckIn = CheckIn::where('guest_id', $state)
                                        ->whereNull('date_of_departure')
                                        ->latest('date_of_arrival')
                                        ->first();

                                    if ($latestCheckIn) {
                                        $set('room_id', $latestCheckIn->room_id);
                                        $this->guestRoomId = $latestCheckIn->room_id;
                                        $this->guestRoomNo = $latestCheckIn->room->room_no;
                                    } else {
                                        $this->guestRoomId = null;
                                        $this->guestRoomNo = null;
                                    }
                                }
                            }),

                        Select::make('room_id')
                            ->label('Room')
                            ->options(Room::query()
                                ->orderBy('room_no')
                                ->get()
                                ->mapWithKeys(function (Room $room) {
                                    return [$room->id => "{$room->room_no} ({$room->building}, Floor {$room->floor})"];
                                }))
                            ->searchable(['room_no', 'building', 'floor'])
                            ->preload()
                            ->helperText(fn () => $this->guestRoomNo ? "Latest active check-in room: {$this->guestRoomNo}" : ''),

                        Select::make('scan_type')
                            ->label('Scan Type')
                            ->options([
                                'check_in' => 'Check In',
                                'check_out' => 'Check Out',
                                'meal' => 'Meal',
                                'door_access' => 'Door Access',
                            ])
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        if (! isset($data['guest_id']) || ! isset($data['room_id'])) {
            Notification::make()
                ->title('Please select both a guest and a room')
                ->danger()
                ->send();

            return;
        }

        $guest = Guest::find($data['guest_id']);
        $room = Room::find($data['room_id']);

        if (! $guest || ! $room) {
            Notification::make()
                ->title('Invalid guest or room selected')
                ->danger()
                ->send();

            return;
        }

        // Process the scan based on scan type
        switch ($data['scan_type']) {
            case 'check_in':
                $this->processCheckIn($guest, $room);
                break;

            case 'check_out':
                $this->processCheckOut($guest, $room);
                break;

            case 'meal':
                $this->processMeal($guest, $room);
                break;

            case 'door_access':
                $this->processDoorAccess($guest, $room);
                break;
        }

        // Reset the form
        $this->form->fill();
    }

    private function processCheckIn($guest, $room): void
    {
        // Check if there's already an active check-in for this guest and room
        $activeCheckIn = CheckIn::where('guest_id', $guest->id)
            ->where('room_id', $room->id)
            ->whereNull('date_of_departure')
            ->first();

        if ($activeCheckIn) {
            Notification::make()
                ->title("{$guest->first_name} {$guest->last_name} is already checked in to Room {$room->room_no}")
                ->warning()
                ->send();

            return;
        }

        // Check room status
        if ($room->status === 'maintenance') {
            Notification::make()
                ->title("Room {$room->room_no} is under maintenance and cannot be checked into")
                ->danger()
                ->send();

            return;
        }

        // Create a new check-in record
        $checkIn = new CheckIn;
        $checkIn->guest_id = $guest->id;
        $checkIn->room_id = $room->id;
        $checkIn->date_of_arrival = Carbon::now();
        $checkIn->save();

        // Update room status to occupied
        $room->status = 'occupied';
        $room->save();

        Notification::make()
            ->title("Successfully checked in {$guest->first_name} {$guest->last_name} to Room {$room->room_no}")
            ->success()
            ->send();
    }

    private function processCheckOut($guest, $room): void
    {
        // Find active check-in for this guest and room
        $activeCheckIn = CheckIn::where('guest_id', $guest->id)
            ->where('room_id', $room->id)
            ->whereNull('date_of_departure')
            ->first();

        if (! $activeCheckIn) {
            Notification::make()
                ->title("{$guest->first_name} {$guest->last_name} is not currently checked in to Room {$room->room_no}")
                ->warning()
                ->send();

            return;
        }

        // Set the departure date to now
        $activeCheckIn->date_of_departure = Carbon::now();
        $activeCheckIn->save();

        // Check if there are any other active check-ins for this room
        $otherActiveCheckIns = CheckIn::where('room_id', $room->id)
            ->where('id', '!=', $activeCheckIn->id)
            ->whereNull('date_of_departure')
            ->count();

        // If no other active check-ins, update room status to available
        if ($otherActiveCheckIns === 0) {
            $room->status = 'available';
            $room->save();
        }

        Notification::make()
            ->title("Successfully checked out {$guest->first_name} {$guest->last_name} from Room {$room->room_no}")
            ->success()
            ->send();
    }

    private function processMeal($guest, $room): void
    {
        // Find active check-in for this guest
        $activeCheckIn = CheckIn::where('guest_id', $guest->id)
            ->whereNull('date_of_departure')
            ->first();

        if (! $activeCheckIn) {
            Notification::make()
                ->title("{$guest->first_name} {$guest->last_name} is not currently checked in")
                ->warning()
                ->send();

            return;
        }

        // Logic for recording a meal would go here
        // This is just a placeholder notification
        Notification::make()
            ->title("Meal recorded for {$guest->first_name} {$guest->last_name}")
            ->success()
            ->send();
    }

    private function processDoorAccess($guest, $room): void
    {
        // Find active check-in for this guest and room
        $activeCheckIn = CheckIn::where('guest_id', $guest->id)
            ->where('room_id', $room->id)
            ->whereNull('date_of_departure')
            ->first();

        if (! $activeCheckIn) {
            Notification::make()
                ->title("{$guest->first_name} {$guest->last_name} does not have access to Room {$room->room_no}")
                ->warning()
                ->send();

            return;
        }

        // Logic for recording door access would go here
        // This is just a placeholder notification
        Notification::make()
            ->title("Door access granted to {$guest->first_name} {$guest->last_name} for Room {$room->room_no}")
            ->success()
            ->send();
    }
}
