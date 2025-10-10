<?php

namespace App\Filament\Resources\Tenant\GuestResource\Pages;

use App\Filament\Resources\Tenant\GuestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Notifications\Notification;
use App\Models\Room;

class CreateGuest extends CreateRecord
{
    protected static string $resource = GuestResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(
            Auth::guard('tenant')->check() && 
            Auth::guard('tenant')->user()->can('create guest'),
            403
        );
    }
    
    protected function afterCreate(): void
    {
        // Get the created guest
        $guest = $this->record;
        
        // If it's a resident with an assigned room, show a notification about the automatic check-in
        if ($guest->type === 'RESIDENT' && $guest->assigned_room_id) {
            $room = Room::find($guest->assigned_room_id);
            $roomLabel = $room ? $room->room_no . ' (' . $room->building . ')' : 'assigned room';
            
            Notification::make()
                ->title($guest->first_name . ' ' . $guest->last_name . ' has been automatically checked in')
                ->body('The resident has been automatically checked in to ' . $roomLabel . ' with today\'s date. No check-out date has been set.')
                ->success()
                ->send();
        }
    }
}
