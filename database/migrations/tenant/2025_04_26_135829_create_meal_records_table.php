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
        Schema::create('meal_records', function (Blueprint $table) {
            $table->id(); // Changed from uuid to auto-incrementing ID
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete(); // Changed from foreignUuid to foreignId
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meal_id')->constrained()->cascadeOnDelete();
            $table->dateTime('date_of_transit');
            $table->string('transit_type')->default('entered');
            $table->string('activity_type')->default('Meal Record');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
