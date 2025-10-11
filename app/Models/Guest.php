<?php

namespace App\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Guest extends Model
{
	use UsesTenantConnection;
    use HasFactory;

    protected $fillable = [
        'trn',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'nationality',
        'marital_status',
        'date_of_birth',
        'sex',
        'age_type',
        'photo',
        'medical_history',
        'type',
        'is_active',
        'qr_code',
        // New population management fields
        'pps_number',
        'iban',
        'job_title',
        'assigned_room_id',
        'authorized_absence',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'authorized_absence' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($guest) {
            // Generate QR code for the guest
            $guest->generateQrCode();
            
            // Automatically create check-in record for residents with an assigned room
            if ($guest->type === 'RESIDENT' && $guest->assigned_room_id) {
                $checkIn = new CheckIn();
                $checkIn->guest_id = $guest->id;
                $checkIn->room_id = $guest->assigned_room_id;
                $checkIn->date_of_arrival = now(); // Current date as check-in date
                // No date_of_departure (leaving it null to indicate active check-in)
                $checkIn->save();
                
                // Generate QR code for room access
                if ($guest->assignedRoom) {
                    $guest->assignedRoom->generateGuestRoomQrCode($guest);
                }
                
                // Update room status to occupied
                $room = Room::find($guest->assigned_room_id);
                if ($room && $room->status !== 'maintenance') {
                    $room->status = 'occupied';
                    $room->save();
                }
            }
        });
    }

    public function generateQrCode()
    {
		$tenant = Tenant::current();

		if(!$tenant){
			$tenant = "default";
		}

        $qrPath = $tenant.'/qrcodes/guests/';
        $fileName = 'guest_' . $this->id . '.png';
        
        // QR code content - use the guest.show route which is now properly defined
        $qrContent = route('guest.show', $this->id);
        
        // Create storage directory if it doesn't exist
        if (!Storage::exists('public/' . $qrPath)) {
            Storage::makeDirectory('public/' . $qrPath);
        }
        
        // Generate QR code
        $qrCode = QrCode::size(300)->generate($qrContent);
        Storage::put('public/' . $qrPath . $fileName, $qrCode);
        
        // Update the model
        $this->qr_code = $qrPath . $fileName;
        $this->save();
        
        return $this->qr_code;
    }

    public function getQrCodeUrlAttribute()
    {
        return $this->qr_code ? Storage::url($this->qr_code) : null;
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class, 'guest_id');
    }

	//current room
	public function currentRoom()
	{
		//get the latest check-in record
		$latestCheckIn = $this->checkIns()
			->whereNotNull('date_of_arrival')
			->latest('date_of_arrival')
			->first();
		//return the room associated with the latest check-in record
		return $latestCheckIn ? $latestCheckIn->room() : new Room();
	}
	//current room
	public function currentRoomNo()
	{
		//get the latest check-in record
		$latestCheckIn = $this->checkIns()
			->whereNotNull('date_of_arrival')
			->latest('date_of_arrival')
			->first();
		//return the room associated with the latest check-in record
		return $latestCheckIn ? $latestCheckIn->room->room_no : 'N/A';
	}

    /*public function customRequests(): HasMany
    {
        return $this->hasMany(CustomRequest::class, 'guest_id');
    }
	*/

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'check_ins')
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

    /**
     * Get the assigned room for a resident
     */
    public function assignedRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'assigned_room_id');
    }
    
    /**
     * Determines if a field is required based on the guest type
     */
    public function isFieldRequired(string $field): bool
    {
        return match ($field) {
            'TRN' => $this->type === 'RESIDENT',
            'assigned_room_id' => $this->type === 'RESIDENT',
            'pps_number', 'iban', 'job_title' => $this->type === 'STAFF',
            default => false,
        };
    }
    
    /**
     * Get all absence records for this guest
     */
    public function absenceRecords(): HasMany
    {
        return $this->hasMany(AbsenceRecord::class);
    }
    
    /**
     * Get active absences for this guest
     */
    public function activeAbsences()
    {
        return $this->absenceRecords()
            ->where('status', 'active')
            ->whereNull('end_date');
    }
    
    /**
     * Check if the guest is currently absent
     */
    public function isCurrentlyAbsent(): bool
    {
        return $this->activeAbsences()->exists();
    }
    
    /**
     * Get the duration of the current absence in hours
     */
    public function currentAbsenceDuration(): ?int
    {
        $activeAbsence = $this->activeAbsences()->first();
        
        if (!$activeAbsence) {
            return null;
        }
        
        return $activeAbsence->calculateDuration();
    }
    
    /**
     * Record a new absence when a guest checks out
     */
    public function recordAbsence(CheckIn $checkIn): AbsenceRecord
    {
        return AbsenceRecord::recordAbsence($this, $checkIn);
    }
    
    /**
     * Complete all active absences for this guest
     */
    public function completeAbsences(CheckIn $checkIn = null): void
    {
        $now = now();
        
        $this->activeAbsences()->get()->each(function ($absence) use ($now, $checkIn) {
            $absence->end_date = $now;
            $absence->status = 'completed';
            
            if ($checkIn) {
                $absence->check_out_id = $checkIn->id;
            }
            
            $absence->updateDuration();
        });
    }
}
