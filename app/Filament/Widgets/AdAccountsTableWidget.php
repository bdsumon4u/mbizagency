<?php

namespace App\Filament\Widgets;

use App\Filament\Actions\DepositFundAction;
use App\Filament\Pages\OrderHistory;
use App\Filament\Tables\Columns\AdAccountsTable\AdAccountColumn;
use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Models\AdAccount;
use App\Models\Order;
use App\Services\FacebookAdAccountService;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\Computed;

class AdAccountsTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -2;

    protected string $view = 'filament.widgets.ad-accounts-table-widget';

    #[Computed]
    public function stats(): array
    {
        $accounts = AdAccount::query()->whereBelongsTo(Filament::auth()->user())->get();
        $accountsCount = $accounts->count();
        $lastSynced = $accounts->max('synced_at');

        $activeAccountIds = Order::query()
            ->whereIn('ad_account_id', $accounts->pluck('id'))
            ->where('created_at', '>=', now()->subMonth())
            ->pluck('ad_account_id')
            ->unique();

        $inactiveCount = $accountsCount - $activeAccountIds->count();

        return [
            [
                'label' => 'Total Ad Accounts',
                'value' => $accountsCount,
                'subtext' => 'Managed by you',
                'icon' => 'heroicon-o-users',
                'icon_color' => 'text-blue-500',
                'icon_bg' => 'bg-blue-50',
            ],
            [
                'label' => 'Inactive Accounts',
                'value' => $inactiveCount,
                'subtext' => '30 days no orders',
                'icon' => 'heroicon-o-exclamation-triangle',
                'icon_color' => 'text-red-500',
                'icon_bg' => 'bg-red-50',
            ],
            [
                'label' => 'Last Synced',
                'value' => $lastSynced ? $lastSynced->format('h:i A') : 'N/A',
                'subtext' => $lastSynced ? $lastSynced->format('d-M-Y') : 'N/A',
                'icon' => 'heroicon-o-arrow-path',
                'icon_color' => 'text-green-500',
                'icon_bg' => 'bg-green-50',
            ],
        ];
    }

    public function syncSingle(int $id): void
    {
        $account = AdAccount::query()
            ->whereBelongsTo(Filament::auth()->user())
            ->findOrFail($id);

        try {
            app(FacebookAdAccountService::class)->syncSingleAdAccount($account);

            Notification::make()
                ->title('Ad account synced successfully.')
                ->success()
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->title('Ad account sync failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function syncAll(): void
    {
        $accounts = AdAccount::query()
            ->whereBelongsTo(Filament::auth()->user())
            ->get()
            ->filter(fn (AdAccount $account) => $account->status->isActive());

        if ($accounts->isEmpty()) {
            Notification::make()
                ->title('No active ad accounts found to sync.')
                ->warning()
                ->send();

            return;
        }

        try {
            $service = app(FacebookAdAccountService::class);
            foreach ($accounts as $account) {
                $service->syncSingleAdAccount($account);
            }

            Notification::make()
                ->title($accounts->count().' ad accounts synced successfully.')
                ->success()
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->title('Ad accounts sync failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->extraAttributes(['class' => 'ad-accounts-table'])
            ->query(AdAccount::query()->whereBelongsTo(Filament::auth()->user()))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('#')
                    ->rowIndex()
                    ->alignCenter(),
                AdAccountColumn::make('name')
                    ->label('Ad Account')
                    ->searchable(),
                CurrencyColumn::make('spend_cap')
                    ->label('Limit'),
                CurrencyColumn::make('amount_spent')
                    ->label('Spent'),
                DateTimeColumn::make('synced_at'),
            ])
            ->recordAction('orders')
            ->recordActions([
                Action::make('orders')
                    ->label('Orders')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->extraModalOverlayAttributes(['class' => 'orders-modal-overlay'])
                    ->extraModalWindowAttributes(['class' => 'orders-modal-window'])
                    ->modalContent(fn (AdAccount $record) => view('filament.actions.ad-account-view-orders', [
                        'record' => $record,
                        'table' => 'ad-accounts',
                        'orderHistoryClass' => OrderHistory::class,
                    ]))
                    ->modalHeading(fn (AdAccount $record) => $record->name.'- Order History')
                    ->modalCloseButton()
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->extraAttributes(['class' => 'hidden']),
                DepositFundAction::make()->button(),
            ], RecordActionsPosition::BeforeCells)
            ->content(fn () => view('filament.tables.custom-ad-accounts-table'));
    }
}
