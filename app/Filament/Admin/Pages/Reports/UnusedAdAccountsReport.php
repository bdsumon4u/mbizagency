<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\OrderStatus;
use App\Models\AdAccount;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class UnusedAdAccountsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected ?string $heading = 'Unused Ad Accounts Report';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Unused Ad Accounts';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected string $view = 'filament.pages.reports.unused-ad-accounts-report';

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (Table $table): Builder {
                $days = data_get($table->getFilter('timeframe')?->getState(), 'value') ?? '30';

                return AdAccount::query()
                    ->with('user')
                    ->withMax(['orders as last_topup_at' => function (Builder $query) {
                        $query->where('status', OrderStatus::APPROVED);
                    }], 'created_at')
                    ->withCount(['orders as total_topups' => function (Builder $query) {
                        $query->where('status', OrderStatus::APPROVED);
                    }])
                    ->when($days !== 'never', function (Builder $query) use ($days) {
                        $cutoff = now()->subDays((int) $days);
                        $query->whereDoesntHave('orders', function (Builder $orderQuery) use ($cutoff) {
                            $orderQuery->where('status', OrderStatus::APPROVED)
                                ->where('created_at', '>=', $cutoff);
                        });
                    }, function (Builder $query) {
                        // Never had any approved top-up
                        $query->whereDoesntHave('orders', function (Builder $orderQuery) {
                            $orderQuery->where('status', OrderStatus::APPROVED);
                        });
                    })
                    ->orderByDesc('last_topup_at');
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Ad Account')
                    ->searchable()
                    ->description(fn (AdAccount $record): string => 'ID: '.$record->act_id),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (AdAccount $record): ?string => $record->user?->page_name),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('last_topup_at')
                    ->label('Last Topup Date')
                    ->dateTime('M d, Y H:i')
                    ->placeholder('Never'),
                TextColumn::make('days_inactive')
                    ->label('Days Inactive')
                    ->state(function (AdAccount $record): string {
                        if (! $record->last_topup_at) {
                            return 'No Top-up';
                        }
                        $days = (int) now()->diffInDays(Carbon::parse($record->last_topup_at));

                        return $days === 0 ? 'Today' : $days.' days ago';
                    })
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === 'No Top-up' => 'gray',
                        str_contains((string) $state, '60') || str_contains((string) $state, '90') || str_contains((string) $state, '180') => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('total_topups')
                    ->label('Total Top-ups')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('timeframe')
                    ->label('Inactive Period')
                    ->options([
                        '30' => 'Last topup before 30 days',
                        '60' => 'Last topup before 60 days',
                        '90' => 'Last topup before 90 days',
                        '180' => 'Last topup before 180 days',
                        'never' => 'Never topped up (0 approved topups)',
                    ])
                    ->default('30')
                    ->query(fn (Builder $query) => $query),
            ])
            ->paginated([10, 25, 50]);
    }
}
