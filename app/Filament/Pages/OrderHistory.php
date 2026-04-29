<?php

namespace App\Filament\Pages;

use App\Actions\ApproveOrderAction;
use App\Actions\RejectOrderAction;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Filament\Tables\Columns\OrderHistoryTable\AdAccountColumn;
use App\Models\Order;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Number;

class OrderHistory extends Page implements HasTable
{
    use InteractsWithTable;

    public ?int $adAccountId = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected string $view = 'filament.pages.order-history';

    public function table(Table $table): Table
    {
        return self::configureTable($table)
            ->query(
                Order::query()->when(! static::isAdminPanel(), function ($query) {
                    return $query->whereBelongsTo(Filament::auth()->user());
                })->when(static::isAdminPanel(), function ($query) {
                    return $query->with('user');
                })->when($this->adAccountId, function ($query) {
                    return $query->where('ad_account_id', $this->adAccountId);
                })->with('adAccount')
            );
    }

    public static function configureTable(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                DateTimeColumn::make('created_at')
                    ->label('Date-Time'),
                AdAccountColumn::make('adAccount.name')
                    ->searchable(),
                CurrencyColumn::make('usd_amount')
                    ->label('Amount')
                    ->description(function (Order $order) {
                        return Number::currency($order->bdt_amount, 'BDT');
                    }),
                CurrencyColumn::make('dollar_rate', 'BDT')
                    ->label('Dollar Rate'),
                CurrencyColumn::make('spend_cap')
                    ->label('Limit'),
                TextColumn::make('source')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                DateTimeColumn::make('approved_at')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('ad_account_id')
                    ->label('Account')
                    ->relationship('adAccount', 'name', fn ($query) => $query->when(! static::isAdminPanel(), function ($query) {
                        return $query->whereBelongsTo(Filament::auth()->user());
                    }))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('source')
                    ->options(OrderSource::class)
                    ->searchable(),
                SelectFilter::make('status')
                    ->options(OrderStatus::class)
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('orders')
                    ->label('Orders')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalContent(fn (Order $record) => view('filament.actions.ad-account-view-orders', [
                        'record' => $record->adAccount,
                        'table' => 'order-history',
                        'orderHistoryClass' => OrderHistory::class,
                    ]))
                    ->modalHeading('')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->extraAttributes(['class' => 'hidden']),
                ActionGroup::make([
                    Action::make('viewProof')
                        ->label('Proof of Payment')
                        ->icon(Heroicon::OutlinedPhoto)
                        ->color('info')
                        // ->button()
                        ->slideOver()
                        ->modalWidth(Width::Medium)
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalHeading('Proof of Payment')
                        ->modalContent(fn (Order $record) => view('filament.pages.partials.order-history-details', [
                            'record' => $record,
                        ]))
                        ->modalFooterActions([
                            self::printInvoiceAction(),
                            self::approveOrderAction(),
                            self::rejectOrderAction(),
                        ]),
                    self::printInvoiceAction(),
                ]),
            ])
            ->recordAction('viewProof');
    }

    private static function isAdminPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    private static function printInvoiceAction(): Action
    {
        return Action::make('printInvoice')
            ->label('View/Print Invoice')
            ->icon(Heroicon::OutlinedPrinter)
            ->color('gray')
            // ->button()
            ->url(fn (Order $record): string => URL::temporarySignedRoute(
                'orders.invoice',
                now()->addMinutes(30),
                ['order' => $record->id],
            ))
            ->openUrlInNewTab();
    }

    private static function approveOrderAction(): Action
    {
        return Action::make('approveOrder')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->action(function (Order $record): void {
                try {
                    app(ApproveOrderAction::class)($record);
                } catch (Exception $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->cancelParentActions()
            ->requiresConfirmation()
            ->visible(fn (Order $record): bool => static::isAdminPanel() && $record->status !== OrderStatus::APPROVED);
    }

    private static function rejectOrderAction(): Action
    {
        return Action::make('rejectOrder')
            ->label('Reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->action(function (Order $record): void {
                try {
                    app(RejectOrderAction::class)($record);
                } catch (Exception $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();
                }

            })
            ->cancelParentActions()
            ->requiresConfirmation()
            ->visible(fn (Order $record): bool => static::isAdminPanel() && $record->status !== OrderStatus::REJECTED);
    }
}
