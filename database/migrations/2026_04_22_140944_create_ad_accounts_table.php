<?php

use App\Enums\AdAccountStatus;
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
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id()->startingValue(1001);
            $table->foreignId('business_manager_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('name')->index();
            $table->string('act_id')->unique();
            $table->string('status')->index()->default(AdAccountStatus::ACTIVE->value);
            $table->string('currency')->default('USD');
            $table->integer('spend_cap')->nullable();
            $table->integer('amount_spent')->default(0);
            $table->integer('balance')->default(0);
            $table->string('payment_method')->nullable();
            $table->integer('prepaid_fund_added')->default(0);
            $table->integer('billing_threshold')->default(0);

            // Additional metadata
            $table->string('timezone')->nullable();
            $table->string('account_type')->nullable();
            $table->text('description')->nullable();
            $table->string('disable_reason')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
