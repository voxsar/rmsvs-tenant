<?php

namespace App\Console\Commands;

use App\Models\CheckIn;
use Illuminate\Console\Command;

class RegenerateQrCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qr:regenerate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate QR codes for all active check-ins';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting QR code regeneration...');

        // Get all active check-ins
        $checkIns = CheckIn::all();
        
        $this->info("Found {$checkIns->count()} active check-ins");
        
        $bar = $this->output->createProgressBar($checkIns->count());
        $bar->start();
        
        $regenerated = 0;
        
        foreach ($checkIns as $checkIn) {
            if ($checkIn->room && $checkIn->guest) {
                // Ensure the guest-room relationship exists
                $exists = $checkIn->guest->rooms()->where('room_id', $checkIn->room->id)->exists();
                
                if (!$exists) {
                    // Create the relationship
                    $checkIn->guest->rooms()->attach($checkIn->room->id);
                }
                
                // Generate QR code
                $qrCode = $checkIn->room->generateGuestRoomQrCode($checkIn->guest);
                
                if ($qrCode) {
                    $regenerated++;
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Successfully regenerated $regenerated QR codes");

        return 0;
    }
}