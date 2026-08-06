<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\OrderStatus;
use App\Models\AdAccount;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class UnusedAdAccountsTableWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '';

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
                        'never' => 'No approved topups',
                    ])
                    ->default('30')
                    ->query(fn (Builder $query) => $query)
                    ->searchable(),
            ])
            ->paginated([10, 25, 50]);
    }
}
