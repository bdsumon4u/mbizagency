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
        Schema::create('payment_verifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('verifiable'); // verifiable_type, verifiable_id
            $table->foreignId('incoming_sms_id')->nullable()->constrained('incoming_sms')->nullOnDelete();
            $table->string('screenshot_path')->nullable();
            $table->string('provider')->nullable()->index();
            $table->string('trx_id')->nullable()->index();
            $table->decimal('amount', 12, 2)->nullable()->index();
            $table->string('sender_account')->nullable()->index();
            $table->string('receiver_account')->nullable()->index();
            $table->dateTime('transaction_time')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->boolean('is_suspicious')->default(false)->index();
            $table->json('ai_response')->nullable();
            $table->string('verification_status')->default('pending')->index(); // pending, approved, manual_review, rejected, suspicious
            $table->text('status_reason')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_verifications');
    }
};
