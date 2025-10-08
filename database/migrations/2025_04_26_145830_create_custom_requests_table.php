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
        Schema::create('custom_requests', function (Blueprint $table) {
            $table->id(); // Changed from uuid to auto-incrementing ID
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete(); // Changed from foreignUuid to foreignId
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consumable_id')->nullable()->cascadeOnDelete();
            $table->string('request_type');
            $table->text('description')->nullable();
            $table->string('status')->default('PENDING');
            $table->text('response_msg')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_requests');
    }
};
