<?php

use App\Enums\BusinessManagerStatus;
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
        Schema::create('business_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('bm_id')->unique();
            $table->text('access_token');
            $table->string('ad_act_prefix')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default(BusinessManagerStatus::NONE);
            $table->string('currency')->default('USD');
            $table->integer('balance')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_managers');
    }
};
