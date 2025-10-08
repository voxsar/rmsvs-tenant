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
        Schema::create('absence_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->onDelete('cascade');
            $table->datetime('start_date');
            $table->datetime('end_date')->nullable();
            $table->boolean('is_authorized')->default(false);
            $table->text('notes')->nullable();
            $table->string('status')->default('active'); // 'active', 'completed', 'cancelled'
            $table->integer('duration_hours')->nullable();
            $table->foreignId('check_in_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('check_out_id')->nullable()->constrained('check_ins')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_records');
    }
};
