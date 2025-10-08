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
        Schema::create('scanners', function (Blueprint $table) {
            $table->id();
			$table->string('name');
			//location
			$table->string('location');
			//status
			$table->enum('status', ['active', 'inactive'])->default('active');
			//type
			$table->string('type' )->default('door');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scanners');
    }
};
