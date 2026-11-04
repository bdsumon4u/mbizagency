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
        Schema::table('payment_verifications', function (Blueprint $table) {
            $table->string('sender_name')->nullable()->after('sender_account');
            $table->decimal('fee', 12, 2)->nullable()->after('amount');
            $table->decimal('total_amount', 12, 2)->nullable()->after('fee');
            $table->decimal('balance', 14, 2)->nullable()->after('total_amount');
            $table->string('txn_type')->nullable()->after('balance');
            $table->string('beneficiary_bank')->nullable()->after('txn_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_verifications', function (Blueprint $table) {
            $table->dropColumn(['sender_name', 'fee', 'total_amount', 'balance', 'txn_type', 'beneficiary_bank']);
        });
    }
};
