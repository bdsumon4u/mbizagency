<?php

namespace App\Filament\Pages;

use App\Filament\Actions\DepositFundAction;
use App\Models\AdAccount;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;

class SimpleOrderHistoryPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.simple-order-history-page';

    public string $search = '';

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    #[Computed]
    public function stats(): array
    {
        $accounts = AdAccount::query()->whereBelongsTo(Filament::auth()->user())->get();
        $accountsCount = $accounts->count();
        $totalBalance = $accounts->sum('balance');
        $activeAccounts = $accounts->filter(fn ($account) => $account->status->isActive())->count();

        return [
            [
                'label' => 'Total Accounts',
                'value' => (string) $accountsCount,
                'subtext' => 'All Time',
                'icon' => 'heroicon-o-wallet',
                'icon_color' => 'text-red-500',
                'icon_bg' => 'bg-red-50',
            ],
            [
                'label' => 'Total Balance',
                'value' => '$'.number_format($totalBalance, 2),
                'subtext' => 'All Accounts',
                'icon' => 'heroicon-o-currency-dollar',
                'icon_color' => 'text-green-500',
                'icon_bg' => 'bg-green-50',
            ],
            [
                'label' => 'Active Accounts',
                'value' => (string) $activeAccounts,
                'subtext' => 'Approved',
                'icon' => 'heroicon-o-check-circle',
                'icon_color' => 'text-blue-500',
                'icon_bg' => 'bg-blue-50',
            ],
        ];
    }

    #[Computed]
    public function orders(): array
    {
        return Order::query()
            ->whereBelongsTo(Filament::auth()->user())
            ->with('adAccount')
            ->when($this->search, function ($query) {
                $query->whereHas('adAccount', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('act_id', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'account_id' => $order->adAccount?->act_id ?? 'N/A',
                'name' => $order->adAccount?->name ?? 'Deleted Account',
                'balance' => '$'.number_format($order->adAccount?->balance ?? 0, 2),
                'status' => $order->status->getLabel(),
                'status_color' => $order->status->getColor(),
                'date' => $order->created_at->format('d/m/y'),
                'time' => $order->created_at->format('h:i A'),
                'amount' => '$'.number_format($order->usd_amount, 2),
                'amount_bdt' => 'Tk. '.number_format($order->bdt_amount, 2),
                'dollar_rate' => 'Tk. '.number_format($order->dollar_rate, 2),
                'limit_usd' => '$'.number_format($order->new_limit ?? 0, 2),
                'limit_old' => '$'.number_format($order->old_limit ?? 0, 2),
                'remaining' => '$'.number_format($order->adAccount?->balance ?? 0, 2),
            ])
            ->toArray();
    }

    public function topUp(string $actId): void
    {
        $adAccount = AdAccount::query()
            ->whereBelongsTo(Filament::auth()->user())
            ->where('act_id', $actId)
            ->first();

        if ($adAccount) {
            $this->mountTableAction('add_fund', $adAccount->getKey());
        }
    }

    public function openAccount(string $id): void
    {
        // Dummy method for demonstration
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AdAccount::query()->whereBelongsTo(Filament::auth()->user()))
            ->columns([])
            ->recordActions([
                DepositFundAction::make()->button(),
            ]);
    }
}
