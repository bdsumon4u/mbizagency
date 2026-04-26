<?php

namespace Database\Seeders;

use App\Models\PriceRate;
use Illuminate\Database\Seeder;

class PriceRateSeeder extends Seeder
{
    /**
     * @var array<int, int>
     */
    private const PRICE_RATES = [
        10 => 135,
        50 => 130,
        100 => 128,
        500 => 126,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PRICE_RATES as $minUsd => $dollarRate) {
            PriceRate::create([
                'min_usd' => $minUsd,
                'dollar_rate' => $dollarRate,
            ]);
        }
    }
}
