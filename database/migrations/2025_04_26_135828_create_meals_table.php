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
        Schema::create('meals', function (Blueprint $table) {
            $table->id(); // Changed from uuid to auto-incrementing ID
            //$table->foreignId('check_in_id')->constrained('check_ins')->cascadeOnDelete(); // Changed from foreignUuid to foreignId
            $table->time('range_start');
			$table->time('range_end');
            $table->string('meal_type');
            $table->json('week_day');
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
