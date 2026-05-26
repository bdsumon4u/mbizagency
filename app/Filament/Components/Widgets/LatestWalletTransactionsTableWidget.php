<?php

namespace App\Filament\Components\Widgets;

use App\Filament\Pages\Wallet;
use App\Models\WalletTransaction;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestWalletTransactionsTableWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public static function getEloquentQuery(): Builder
    {
        return WalletTransaction::query()
            ->whereBelongsTo(Filament::auth()->user())
            ->latest()
            ->limit(3);
    }

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'admin' && static::getEloquentQuery()->exists();
    }

    public function table(Table $table): Table
    {
        $table = Wallet::configureTable($table)
            ->heading(null)
            ->extraAttributes(['class' => 'wallet-transactions-table latest-wallet-transactions-table'])
            ->query(fn (): Builder => static::getEloquentQuery())
            ->paginated(false);

        $table->headerActions([
            Action::make('viewAll')
                ->label('View All')
                ->url(fn () => Wallet::getUrl())
                ->button()
                ->color('primary')
                ->size('sm'),
        ]);

        $table->filters([]);

        foreach ($table->getColumns() as $column) {
            $column->searchable(false);
        }

        return $table;
    }
}
