<?php

namespace App\Filament\Admin\Pages\Reports;

use App\Enums\OrderStatus;
use App\Models\User;
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

class TopCustomersReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected ?string $heading = 'Top Customers Report';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Top Customers';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected string $view = 'filament.pages.reports.top-customers-report';

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (Table $table): Builder {
                $month = (int) (data_get($table->getFilter('month')?->getState(), 'value') ?? now()->month);
                $year = (int) (data_get($table->getFilter('year')?->getState(), 'value') ?? now()->year);

                return User::query()
                    ->withSum(['orders as total_usd' => function (Builder $query) use ($month, $year) {
                        $query->where('status', OrderStatus::APPROVED)
                            ->whereMonth('created_at', $month)
                            ->whereYear('created_at', $year);
                    }], 'usd_amount')
                    ->withSum(['orders as total_bdt' => function (Builder $query) use ($month, $year) {
                        $query->where('status', OrderStatus::APPROVED)
                            ->whereMonth('created_at', $month)
                            ->whereYear('created_at', $year);
                    }], 'bdt_amount')
                    ->withCount(['orders as total_orders' => function (Builder $query) use ($month, $year) {
                        $query->where('status', OrderStatus::APPROVED)
                            ->whereMonth('created_at', $month)
                            ->whereYear('created_at', $year);
                    }])
                    ->having('total_usd', '>', 0)
                    ->orderByDesc('total_usd');
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (User $record): string => implode(' • ', array_filter([$record->page_name, $record->email]))),
                TextColumn::make('total_orders')
                    ->label('Approved Orders')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('total_usd')
                    ->label('Total Deposit (USD)')
                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) ($state ?? 0), 2))
                    ->sortable(),
                TextColumn::make('total_bdt')
                    ->label('Total Deposit (BDT)')
                    ->formatStateUsing(fn ($state): string => '৳'.number_format((float) ($state ?? 0), 2))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('month')
                    ->label('Month')
                    ->options([
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December',
                    ])
                    ->default((int) now()->month)
                    ->query(fn (Builder $query) => $query),
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(function (): array {
                        $currentYear = (int) now()->year;
                        $years = [];
                        for ($y = $currentYear; $y >= $currentYear - 3; $y--) {
                            $years[$y] = (string) $y;
                        }

                        return $years;
                    })
                    ->default((int) now()->year)
                    ->query(fn (Builder $query) => $query),
            ])
            ->paginated([10, 25, 50]);
    }
}
