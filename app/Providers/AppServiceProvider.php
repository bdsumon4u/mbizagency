<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\WalletTransaction;
use App\Observers\OrderObserver;
use App\Observers\WalletTransactionObserver;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Table::configureUsing(function (Table $table) {
            $table->defaultDateTimeDisplayFormat('d M Y h:i A');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);
        WalletTransaction::observe(WalletTransactionObserver::class);
    }
}
