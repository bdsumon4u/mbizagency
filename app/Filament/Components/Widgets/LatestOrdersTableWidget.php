<?php

namespace App\Filament\Components\Widgets;

use App\Filament\Actions\DepositFundAction;
use App\Filament\Pages\OrderHistory;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\URL;

class LatestOrdersTableWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getInvoiceUrl(Order $record): string
    {
        return URL::temporarySignedRoute(
            'orders.invoice',
            now()->addMinutes(30),
            ['order' => $record->id]
        );
    }

    public function table(Table $table): Table
    {
        $table = OrderHistory::configureTable($table)
            ->heading(null)
            ->extraAttributes(['class' => 'latest-orders-table'])
            ->query(
                Order::query()
                    ->with('adAccount')
                    ->whereBelongsTo(Filament::auth()->user())
                    ->latest()
                    ->limit(3)
            )
            ->recordAction('orders')
            ->recordActions([
                Action::make('orders')
                    ->label('Orders')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->extraModalOverlayAttributes(['class' => 'orders-modal-overlay'])
                    ->extraModalWindowAttributes(['class' => 'orders-modal-window'])
                    ->modalContent(fn (Order $record) => view('filament.actions.ad-account-view-orders', [
                        'record' => $record->adAccount,
                        'table' => 'order-history',
                        'orderHistoryClass' => OrderHistory::class,
                    ]))
                    ->modalHeading(fn (Order $record) => $record->adAccount?->name.'- Order History')
                    ->modalCloseButton()
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->extraAttributes(['class' => 'hidden']),
                Action::make('userOrders')
                    ->label('User Orders')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->extraModalOverlayAttributes(['class' => 'orders-modal-overlay'])
                    ->extraModalWindowAttributes(['class' => 'orders-modal-window'])
                    ->modalContent(fn (Order $record) => view('filament.actions.user-view-orders', [
                        'record' => $record->user,
                        'table' => 'order-history',
                        'orderHistoryClass' => OrderHistory::class,
                    ]))
                    ->modalHeading(fn (Order $record) => $record->user->name.' - Order History')
                    ->modalCloseButton()
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->extraAttributes(['class' => 'hidden']),
                DepositFundAction::make()->button(),
            ], RecordActionsPosition::BeforeCells)
            ->content(fn () => view('filament.tables.custom-order-history-table'))
            ->paginated(false)
            ->headerActions([
                Action::make('viewAll')
                    ->label('View All')
                    ->url(fn () => OrderHistory::getUrl())
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
