<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('UPDATE orders SET old_limit = old_limit * 100 WHERE old_limit IS NOT NULL');
        DB::statement('UPDATE orders SET new_limit = new_limit * 100 WHERE new_limit IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('UPDATE orders SET old_limit = old_limit / 100 WHERE old_limit IS NOT NULL');
        DB::statement('UPDATE orders SET new_limit = new_limit / 100 WHERE new_limit IS NOT NULL');
    }
};
