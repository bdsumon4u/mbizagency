<?php

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id()->startingValue(1001);
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->decimal('usd_amount', 12, 2);
            $table->decimal('dollar_rate', 12, 2);
            $table->decimal('bdt_amount', 12, 2);
            $table->integer('spend_cap')->nullable();
            $table->string('source')->default(OrderSource::USER);
            $table->string('status')->index()->default(OrderStatus::PENDING);
            $table->text('note')->nullable();
            $table->string('screenshot')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
