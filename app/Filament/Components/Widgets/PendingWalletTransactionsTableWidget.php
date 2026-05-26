<?php

namespace App\Filament\Components\Widgets;

use App\Enums\WalletTransactionStatus;
use App\Filament\Pages\Wallet;
use App\Models\WalletTransaction;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingWalletTransactionsTableWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -2;

    public static function getEloquentQuery(): Builder
    {
        return WalletTransaction::query()
            ->where('status', WalletTransactionStatus::PENDING)
            ->when(
                Filament::getCurrentPanel()?->getId() !== 'admin',
                fn (Builder $query): Builder => $query->whereBelongsTo(Filament::auth()->user())
            );
    }

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin' || static::getEloquentQuery()->exists();
    }

    public function table(Table $table): Table
    {
        return Wallet::configureTable($table)
            ->heading(null)
            ->extraAttributes(['class' => 'pending-wallet-transactions-table'])
            ->query(fn (): Builder => static::getEloquentQuery());
    }
}
