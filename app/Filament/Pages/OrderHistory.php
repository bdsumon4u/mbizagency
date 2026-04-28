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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected string $view = 'filament.pages.order-history';

    public function table(Table $table): Table
    {
        return self::configureTable($table);
    }

    public static function configureTable(Table $table): Table
    {
        $isAdminPanel = Filament::getCurrentPanel()?->getId() === 'admin';

        return $table
            ->query(
                Order::query()->when(! $isAdminPanel, function ($query) {
                    return $query->whereBelongsTo(Filament::auth()->user());
                })->when($isAdminPanel, function ($query) {
                    return $query->with('user');
                })->with('adAccount')
            )
            ->defaultSort('id', 'desc')
            ->columns([
                DateTimeColumn::make('created_at'),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->visible($isAdminPanel),
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
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload()
                    ->visible($isAdminPanel),
                SelectFilter::make('ad_account_id')
                    ->label('Account')
                    ->relationship('adAccount', 'name')
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
                            self::approveOrderAction($isAdminPanel),
                            self::rejectOrderAction($isAdminPanel),
                        ]),
                    self::printInvoiceAction(),
                ]),
            ])
            ->recordAction('viewProof');
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

    private static function approveOrderAction(bool $isAdminPanel): Action
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
            ->visible(fn (Order $record): bool => $isAdminPanel && $record->status !== OrderStatus::APPROVED);
    }

    private static function rejectOrderAction(bool $isAdminPanel): Action
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
            ->visible(fn (Order $record): bool => $isAdminPanel && $record->status !== OrderStatus::REJECTED);
    }
}
