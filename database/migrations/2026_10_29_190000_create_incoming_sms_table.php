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
        Schema::create('incoming_sms', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_message_id')->nullable()->index();
            $table->string('channel_or_chat_id')->nullable()->index();
            $table->text('raw_message');
            $table->string('provider')->nullable()->index(); // bkash, nagad, rocket, upay, bank, etc.
            $table->string('sender_title')->nullable()->index(); // e.g. 'bKash', '16216', 'NAGAD'
            $table->string('trx_id')->nullable()->index();
            $table->decimal('amount', 12, 2)->nullable()->index();
            $table->decimal('fee', 12, 2)->default(0);
            $table->string('sender_account')->nullable()->index();
            $table->string('receiver_account')->nullable()->index();
            $table->string('reference')->nullable();
            $table->dateTime('transaction_time')->nullable();
            $table->string('status')->default('unmatched')->index(); // unmatched, matched, ignored, duplicate
            $table->nullableMorphs('matched'); // matched_type, matched_id
            $table->timestamp('matched_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_sms');
    }
};
