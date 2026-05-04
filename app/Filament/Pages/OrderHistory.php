<?php

namespace App\Filament\Pages;

use App\Actions\ApproveOrderAction;
use App\Actions\RejectOrderAction;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Filament\Tables\Columns\OrderHistoryTable\AdAccountColumn;
use App\Models\AdAccount;
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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;

class OrderHistory extends Page implements HasTable
{
    use InteractsWithTable;

    public ?int $adAccountId = null;

    protected ?string $heading = '';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected string $view = 'filament.pages.order-history';

    #[Computed]
    public function stats(): array
    {
        $user = Filament::auth()->user();

        $accounts = AdAccount::query()
            ->when(! static::isAdminPanel(), function ($query) use ($user) {
                return $query->whereBelongsTo($user);
            })
            ->get();

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
            )
            ->content(fn () => view('filament.tables.custom-order-history-table'));
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
                CurrencyColumn::make('new_limit')
                    ->description(fn (Order $order) => new HtmlString('<del>'.Number::currency($order->old_limit ?? 0, 'USD').'</del>`'))
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
                    ->extraModalOverlayAttributes(['class' => 'orders-modal-overlay'])
                    ->extraModalWindowAttributes(['class' => 'orders-modal-window'])
                    ->modalContent(fn (Order $record) => view('filament.actions.ad-account-view-orders', [
                        'record' => $record->adAccount,
                        'table' => 'order-history',
                        'orderHistoryClass' => OrderHistory::class,
                    ]))
                    ->modalHeading(fn (Order $order) => $order->adAccount->name.'- Order History')
                    ->modalCloseButton()
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
                        ->modalCloseButton()
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
                    // self::printInvoiceAction(),
                ]),
            ])
            ->recordAction('viewProof');
    }

    public function getInvoiceUrl(Order $record): string
    {
        return URL::temporarySignedRoute(
            'orders.invoice',
            now()->addMinutes(30),
            ['order' => $record->id],
        );
    }

    private static function isAdminPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    private static function printInvoiceAction(): Action
    {
        return Action::make('printInvoice')
            ->label('View/Print')
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
