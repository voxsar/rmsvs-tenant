<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'TRN',
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
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($guest) {
            // Generate QR code for the guest
            $guest->generateQrCode();
        });
    }

    public function generateQrCode()
    {
        $qrPath = 'qrcodes/guests/';
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
}
