<?php

namespace App\Filament\Resources\Tenant\CheckInResource\Pages;

use App\Filament\Resources\Tenant\CheckInResource;
use App\Models\CheckIn;
use App\Models\Guest;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class MultiGuestCheckIn extends Page
{
    protected static string $resource = CheckInResource::class;

    protected static string $view = 'filament.resources.check-in-resource.pages.multi-guest-check-in';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Multi-Guest Check-In')
                    ->description('Check in multiple guests to the same room')
                    ->schema([
                        Select::make('guest_ids')
                            ->label('Guests')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                return Guest::query()
                                    ->when($search, function ($query) use ($search) {
                                        $query->where(function ($query) use ($search) {
                                            $query->where('first_name', 'like', "%{$search}%")
                                                ->orWhere('last_name', 'like', "%{$search}%")
                                                ->orWhere('email', 'like', "%{$search}%");
                                        });
                                    })
                                    ->orderBy('first_name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Guest $guest): array => [
                                        $guest->id => $this->formatGuestName($guest),
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelsUsing(fn (array $values): array => Guest::query()
                                ->whereIn('id', $values)
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Guest $guest): array => [
                                    $guest->id => $this->formatGuestName($guest),
                                ])
                                ->toArray())
                            ->required(),
                        DateTimePicker::make('date_of_arrival')
                            ->required(),
                        DateTimePicker::make('date_of_departure')
                            ->after('date_of_arrival'),
                        Select::make('room_id')
                            ->label('Room')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                return Room::query()
                                    ->when($search, function ($query) use ($search) {
                                        $query->where(function ($query) use ($search) {
                                            $query->where('room_no', 'like', "%{$search}%")
                                                ->orWhere('building', 'like', "%{$search}%")
                                                ->orWhere('floor', 'like', "%{$search}%");
                                        });
                                    })
                                    ->orderBy('room_no')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Room $room): array => [
                                        $room->id => $this->formatRoomLabel($room),
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                if (! $value) {
                                    return null;
                                }

                                $room = Room::find($value);

                                return $room ? $this->formatRoomLabel($room) : null;
                            })
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('room_no')
                                    ->required(),
                                Forms\Components\TextInput::make('building')
                                    ->required(),
                                Forms\Components\TextInput::make('floor')
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'available' => 'Available',
                                        'occupied' => 'Occupied',
                                        'maintenance' => 'Maintenance',
                                    ])
                                    ->default('available')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->columnSpanFull(),
                            ]),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $room = Room::find($data['room_id']);

        // Only block check-in if room is under maintenance
        if ($room->status === 'maintenance') {
            Notification::make()
                ->title('Room is under maintenance')
                ->danger()
                ->send();

            return;
        }

        // Set room status to occupied
        $room->status = 'occupied';
        $room->save();

        $guestIds = $data['guest_ids'];
        $successCount = 0;

        foreach ($guestIds as $guestId) {
            // Create check-in record for each guest
            $checkIn = new CheckIn;
            $checkIn->guest_id = $guestId;
            $checkIn->room_id = $data['room_id'];
            $checkIn->date_of_arrival = $data['date_of_arrival'];

            if (isset($data['date_of_departure'])) {
                $checkIn->date_of_departure = $data['date_of_departure'];
            }

            $checkIn->save();

            // Generate QR code
            $guest = Guest::find($guestId);
            $room->generateGuestRoomQrCode($guest);

            $successCount++;
        }

        Notification::make()
            ->title("Successfully checked in {$successCount} guests to Room {$room->room_no}")
            ->success()
            ->send();

        $this->redirect(CheckInResource::getUrl('index'));
    }

    protected function formatGuestName(Guest $guest): string
    {
        $name = trim(collect([$guest->first_name, $guest->last_name])->filter()->implode(' '));

        return $name !== '' ? $name : ($guest->email ?? 'Guest #'.$guest->id);
    }

    protected function formatRoomLabel(Room $room): string
    {
        $details = collect([$room->building, $room->floor])
            ->filter(fn ($value) => filled($value))
            ->implode(' • ');

        return $details === ''
            ? trim((string) $room->room_no)
            : trim((string) $room->room_no).' ('.$details.')';
    }
}
