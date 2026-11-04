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
        Schema::table('incoming_sms', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'incoming_sms_recent_unmatched_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_sms', function (Blueprint $table): void {
            $table->dropIndex('incoming_sms_recent_unmatched_index');
        });
    }
};
