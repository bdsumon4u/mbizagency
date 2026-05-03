<?php

namespace App\Filament\Pages;

use App\Filament\Actions\DepositFundAction;
use App\Filament\Tables\Columns\AdAccountsTable\AdAccountColumn;
use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Models\AdAccount;
use App\Services\FacebookAdAccountService;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Livewire\Attributes\Computed;

class AdAccounts extends Page implements HasTable
{
    use InteractsWithTable;

    protected ?string $heading = '';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected string $view = 'filament.pages.ad-accounts';

    #[Computed]
    public function stats(): array
    {
        $accounts = AdAccount::query()->whereBelongsTo(Filament::auth()->user())->get();
        $totalLimit = $accounts->sum('spend_cap');
        $totalSpent = $accounts->sum('amount_spent');
        $accountsCount = $accounts->count();
        $lastSynced = $accounts->max('synced_at');

        return [
            [
                'label' => 'Total Limit',
                'value' => '$'.number_format($totalLimit, 2),
                'subtext' => 'Across '.$accountsCount.' accounts',
                'icon' => 'heroicon-o-wallet',
                'icon_color' => 'text-red-500',
                'icon_bg' => 'bg-red-50',
            ],
            [
                'label' => 'Total Spent',
                'value' => '$'.number_format($totalSpent, 2),
                'subtext' => 'Across '.$accountsCount.' accounts',
                'icon' => 'heroicon-o-arrow-trending-up',
                'icon_color' => 'text-green-500',
                'icon_bg' => 'bg-green-50',
            ],
            [
                'label' => 'Last Synced',
                'value' => $lastSynced ? $lastSynced->format('h:i A') : 'N/A',
                'subtext' => $lastSynced ? $lastSynced->format('d-M-Y') : 'N/A',
                'icon' => 'heroicon-o-arrow-path',
                'icon_color' => 'text-blue-500',
                'icon_bg' => 'bg-blue-50',
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

    public function table(Table $table): Table
    {
        return $table
            ->query(AdAccount::query()->whereBelongsTo(Filament::auth()->user()))
            ->defaultSort('id', 'desc')
            ->content(fn () => view('filament.tables.custom-ad-accounts-table'))
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
                    ->extraModalOverlayAttributes(['class' => 'batash'])
                    ->extraModalWindowAttributes(['class' => 'hotash'])
                    ->modalContent(fn (AdAccount $record) => view('filament.actions.ad-account-view-orders', [
                        'record' => $record,
                        'table' => 'ad-accounts',
                        'orderHistoryClass' => OrderHistory::class,
                    ]))
                    ->modalHeading('')
                    ->modalSubmitAction(false),
                DepositFundAction::make()->button(),
            ], RecordActionsPosition::BeforeCells);
    }
}
