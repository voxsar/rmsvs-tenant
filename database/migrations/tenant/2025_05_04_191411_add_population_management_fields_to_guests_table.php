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
        Schema::table('guests', function (Blueprint $table) {
            // Fields for Staff
            $table->string('pps_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('job_title')->nullable();

            // Field for Residents (room number will be directly associated)
            $table->integer('assigned_room_id')->nullable();

            // Field to track authorized absences for Residents
            $table->boolean('authorized_absence')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn([
                'pps_number',
                'iban',
                'job_title',
                'assigned_room_id',
                'authorized_absence',
            ]);
        });
    }
};
