<?php

namespace App\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Room extends Model
{
	use UsesTenantConnection;
    use HasFactory;
    
    protected $fillable = [
        'room_no',
        'building',
        'floor',
        'status',
        'max_occupants',
        'description',
    ];
    
    // Room can be associated with check-ins
    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class, 'room_id');
    }
    
    // Many-to-many relationship with guests
    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(Guest::class, 'check_ins')
					->using(CheckIn::class)
					->withPivot('date_of_arrival', 'date_of_departure', 'qr_code')
                    ->withTimestamps();
    }

    public function customrequests(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'custom_requests')
					->using(CustomRequest::class)
					->withPivot('request_type', 'description', 'status', 'response_msg', 'responded_at')
                    ->withTimestamps();
    }
    
    // Generate QR code for a guest-room association
    public function generateGuestRoomQrCode(Guest $guest)
    {
        $guestRoom = $this->guests()->where('guest_id', $guest->id)->first();
        
        if (!$guestRoom) {
            return null;
        }
        
        $pivotRecord = $guestRoom->pivot;
        
		$tenant = Tenant::current();

		if(!$tenant){
			$tenant = "default";
		}
		
        $qrPath = $tenant.'/qrcodes/guest_rooms/';
        $fileName = 'guest_' . $guest->id . '_room_' . $this->id .'_checkin_'.$pivotRecord->id. '.svg';
        
        // Simplified QR code content with just the essential IDs
        $qrContent = json_encode([
            'guest_id' => $guest->id,
            'room_id' => $this->id
        ]);
        
        // Create storage directory if it doesn't exist
        if (!Storage::exists('public/' . $qrPath)) {
            Storage::makeDirectory('public/' . $qrPath);
        }
        
        // Generate QR code
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)
                ->generate($qrContent);
        
        Storage::put('public/' . $qrPath . $fileName, $qrCode);
        
        // Update the pivot record
        $pivotRecord->qr_code = $qrPath . $fileName;
        $pivotRecord->save();
        
        return $qrPath . $fileName;
    }
    
    /**
     * Get the current number of occupants in the room
     */
    public function getCurrentOccupantsCount()
    {
        return $this->checkIns()
            ->whereNull('date_of_departure')
            ->count();
    }
}
