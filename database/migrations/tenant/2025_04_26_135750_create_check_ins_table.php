<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id(); // Changed from uuid to auto-incrementing ID
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete(); // Changed from foreignUuid to foreignId
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
			$table->string('activity_type')->default('Check-In');
            $table->dateTime('date_of_arrival');
            $table->dateTime('date_of_departure')->nullable();
            $table->string('qr_code')->nullable();
            // Removed legacy room string field
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
