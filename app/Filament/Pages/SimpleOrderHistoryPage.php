<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;

class SimpleOrderHistoryPage extends Page
{
    protected string $view = 'filament.pages.simple-order-history-page';

    public string $search = '';

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    #[Computed]
    public function stats(): array
    {
        return [
            [
                'label' => 'Total Accounts',
                'value' => '12',
                'subtext' => 'All Time',
                'icon' => 'heroicon-o-wallet',
                'icon_color' => 'text-red-500',
                'icon_bg' => 'bg-red-50',
            ],
            [
                'label' => 'Total Balance',
                'value' => '$1,412.48',
                'subtext' => 'All Accounts',
                'icon' => 'heroicon-o-currency-dollar',
                'icon_color' => 'text-green-500',
                'icon_bg' => 'bg-green-50',
            ],
            [
                'label' => 'Active Accounts',
                'value' => '10',
                'subtext' => 'Approved',
                'icon' => 'heroicon-o-check-circle',
                'icon_color' => 'text-blue-500',
                'icon_bg' => 'bg-blue-50',
            ],
        ];
    }

    #[Computed]
    public function adAccounts(): array
    {
        return [
            [
                'id' => '508015572331351',
                'name' => 'US_188_Cyber 32-029_Sheik Mobeen_CloudMobeen_Cloud',
                'balance' => '$93.18',
                'status' => 'Approved',
                'status_color' => 'green',
                'date' => '28/04/26',
                'time' => '11:00 AM',
                'amount' => '$10.00',
                'amount_bdt' => 'BDT 1,350.00',
                'dollar_rate' => 'BDT 135.00',
                'limit_usd' => '$65.00',
                'limit_old' => '$0.00',
                'remaining' => '$65.00',
            ],
            [
                'id' => '1389108508978125',
                'name' => 'US_1194_Cyber 32-008_Atik_Nexus Shop',
                'balance' => '$193.83',
                'status' => 'Verification Processing',
                'status_color' => 'yellow',
                'date' => '28/04/26',
                'time' => '10:57 AM',
                'amount' => '$10.00',
                'amount_bdt' => 'BDT 1,350.00',
                'dollar_rate' => 'BDT 135.00',
                'limit_usd' => '$55.00',
                'limit_old' => '$0.00',
                'remaining' => '$65.00',
            ],
            [
                'id' => '741866791657878',
                'name' => 'US_1209_Cyber 32-019_Kamrul Hasan_Punok019_Kamrul Hasan_Punok',
                'balance' => '$549.72',
                'status' => 'Rejected',
                'status_color' => 'green',
                'date' => '27/04/26',
                'time' => '04:32 PM',
                'amount' => '$20.00',
                'amount_bdt' => 'BDT 2,700.00',
                'dollar_rate' => 'BDT 135.00',
                'limit_usd' => '$221.00',
                'limit_old' => '$0.00',
                'remaining' => '$65.00',
            ],
            [
                'id' => '765131882802829',
                'name' => 'US_1204_Cyber 32-010_Evan_Resume',
                'balance' => '$309.67',
                'status' => 'Verification Processing',
                'status_color' => 'yellow',
                'date' => '27/04/26',
                'time' => '08:38 AM',
                'amount' => '$10.00',
                'amount_bdt' => 'BDT 1,350.00',
                'dollar_rate' => 'BDT 135.00',
                'limit_usd' => '$35.00',
                'limit_old' => '$0.00',
                'remaining' => '$0.00',
            ],
            [
                'id' => '3355539781269135',
                'name' => 'US_1240_Cyber 32-061_Mominul Islam_Mi 3',
                'balance' => '$122.70',
                'status' => 'Approved',
                'status_color' => 'green',
                'date' => '27/04/26',
                'time' => '08:36 AM',
                'amount' => '$300.00',
                'amount_bdt' => 'BDT 38,400.00',
                'dollar_rate' => 'BDT 128.00',
                'limit_usd' => '$20.00',
                'limit_old' => '$0.00',
                'remaining' => '$0.00',
            ],
            [
                'id' => '1565640794065804',
                'name' => 'US_1102_Cyber 32-023_Mominul Islam_Mi 1Islam_Mi 1',
                'balance' => '$124.29',
                'status' => 'Verification Processing',
                'status_color' => 'yellow',
                'date' => '27/04/26',
                'time' => '08:33 AM',
                'amount' => '$10.00',
                'amount_bdt' => 'BDT 1,350.00',
                'dollar_rate' => 'BDT 135.00',
                'limit_usd' => '$55.00',
                'limit_old' => '$0.00',
                'remaining' => '$0.00',
            ],
            [
                'id' => '1252419719191951',
                'name' => 'US_1183_Cyber 32-028_Tuhin_Return Car',
                'balance' => '$19.06',
                'status' => 'Approved',
                'status_color' => 'green',
                'date' => '27/04/26',
                'time' => '06:10 AM',
                'amount' => '$15.00',
                'amount_bdt' => 'BDT 2,025.00',
                'dollar_rate' => 'BDT 135.00',
                'limit_usd' => '$35.00',
                'limit_old' => '$0.00',
                'remaining' => '$65.00',
            ],
        ];
    }

    public function topUp(string $id)
    {
        // Dummy method for demonstration
    }

    public function openAccount(string $id)
    {
        // Dummy method for demonstration
    }
}
