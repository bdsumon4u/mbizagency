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
        Schema::create('price_rates', function (Blueprint $table) {
            $table->id()->startingValue(1001);
            $table->foreignId('ad_account_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('min_usd', 12, 2);
            $table->decimal('dollar_rate', 12, 2);
            $table->timestamps();

            $table->unique(['ad_account_id', 'min_usd']);
            $table->index(['ad_account_id', 'min_usd']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_rates');
    }
};
