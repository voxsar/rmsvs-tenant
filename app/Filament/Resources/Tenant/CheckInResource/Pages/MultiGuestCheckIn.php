<?php

namespace App\Filament\Resources\Tenant\CheckInResource\Pages;

use App\Filament\Resources\Tenant\CheckInResource;
use App\Models\CheckIn;
use App\Models\Guest;
use App\Models\Room;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
                            ->options(Guest::query()->orderBy('first_name')->get()->mapWithKeys(fn (Guest $guest) => [$guest->id => "{$guest->first_name} {$guest->last_name}"]))
                            ->searchable()
                            ->preload()
                            ->required(),
                        DateTimePicker::make('date_of_arrival')
                            ->required(),
                        DateTimePicker::make('date_of_departure')
                            ->after('date_of_arrival'),
                        Select::make('room_id')
                            ->label('Room')
                            ->options(Room::query()->orderBy('room_no')->get()->pluck('room_no', 'id'))
                            ->searchable()
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
            $checkIn = new CheckIn();
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
}