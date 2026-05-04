<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Actions\DepositFundAction;
use App\Filament\Pages\OrderHistory;
use App\Models\AdAccount;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class PendingOrdersTableWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    public ?int $adAccountId = null;

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
            })
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
                DepositFundAction::make()->button(),
            ], RecordActionsPosition::BeforeCells)
            ->content(fn () => view('filament.tables.custom-order-history-table'));
    }
}
