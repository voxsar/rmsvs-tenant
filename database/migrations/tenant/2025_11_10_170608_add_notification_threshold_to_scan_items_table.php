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
        Schema::table('scan_items', function (Blueprint $table) {
            $table->integer('notification_threshold')->nullable()->after('notify_if_missed');
            $table->string('notification_threshold_unit')->default('hours')->after('notification_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_items', function (Blueprint $table) {
            $table->dropColumn(['notification_threshold', 'notification_threshold_unit']);
        });
    }
};
