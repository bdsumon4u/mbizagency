<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Pages\OrderHistory;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingOrdersTableWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    public function table(Table $table): Table
    {
        return OrderHistory::configureTable($table)
            ->heading(null)
            ->query(function (): Builder {
                return Order::query()
                    ->where('status', OrderStatus::PENDING)
                    ->with('adAccount')
                    ->when(
                        Filament::getCurrentPanel()?->getId() !== 'admin',
                        fn (Builder $query): Builder => $query->whereHas('adAccount', function (Builder $adAccountsQuery): Builder {
                            return $adAccountsQuery->whereBelongsTo(Filament::auth()->user());
                        })
                    );
            });
    }
}
