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
        Schema::table('incoming_sms', function (Blueprint $table) {
            $table->decimal('balance', 14, 2)->nullable()->after('amount');
            $table->string('txn_type')->nullable()->after('provider'); // e.g. 'Fund Transfer', 'CRM/CDM Cash Deposit', 'CITYTOUCH TXN'
        });

        Schema::table('payment_verifications', function (Blueprint $table) {
            $table->string('receiver_name')->nullable()->after('receiver_account');
            $table->string('reference')->nullable()->after('trx_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_sms', function (Blueprint $table) {
            $table->dropColumn(['balance', 'txn_type']);
        });

        Schema::table('payment_verifications', function (Blueprint $table) {
            $table->dropColumn(['receiver_name', 'reference']);
        });
    }
};
