<?php

namespace App\Filament\Admin\Resources\IncomingSms\Tables;

use App\Enums\IncomingSmsStatus;
use App\Models\IncomingSms;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IncomingSmsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'http_webhook' => 'HTTP Webhook',
                        'telegram' => 'Telegram',
                        default => (string) ($state ?: 'Unknown'),
                    })
                    ->colors([
                        'info' => 'http_webhook',
                        'primary' => 'telegram',
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provider')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
                        'rocket' => 'Rocket',
                        'upay' => 'Upay',
                        'brac_bank' => 'BRAC Bank',
                        'city_bank' => 'City Bank',
                        'dbbl' => 'DBBL',
                        'ebl' => 'EBL',
                        'bank' => 'Bank',
                        default => ucfirst((string) ($state ?: 'Unknown')),
                    })
                    ->colors([
                        'pink' => 'bkash',
                        'danger' => 'nagad',
                        'purple' => 'rocket',
                        'warning' => 'upay',
                        'info' => fn ($state) => in_array($state, ['brac_bank', 'city_bank', 'dbbl', 'ebl', 'bank'], true),
                    ])
                    ->sortable()
                    ->searchable(),
                TextColumn::make('trx_id')
                    ->label('TrxID / Ref')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->placeholder('—')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('receiver_account')
                    ->label('Receiver A/C')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('sender_account')
                    ->label('Sender A/C')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('transaction_time')
                    ->label('Tx Time')
                    ->dateTime('d M Y h:i A')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('raw_message')
                    ->label('SMS Content')
                    ->limit(50)
                    ->tooltip(fn (IncomingSms $record): string => $record->raw_message),
                TextColumn::make('matched_type')
                    ->label('Matched With')
                    ->formatStateUsing(function ($state, IncomingSms $record) {
                        if (! $state || ! $record->matched_id) {
                            return '—';
                        }

                        return class_basename((string) $state).' #'.$record->matched_id;
                    })
                    ->badge()
                    ->color('success')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        IncomingSmsStatus::Unmatched->value => 'Unmatched',
                        IncomingSmsStatus::Matched->value => 'Matched',
                        IncomingSmsStatus::Duplicate->value => 'Duplicate',
                        IncomingSmsStatus::Ignored->value => 'Ignored',
                    ]),
                SelectFilter::make('provider')
                    ->options([
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
                        'rocket' => 'Rocket',
                        'upay' => 'Upay',
                        'brac_bank' => 'BRAC Bank',
                        'city_bank' => 'City Bank',
                        'dbbl' => 'DBBL',
                        'ebl' => 'EBL',
                        'bank' => 'Bank',
                        'other' => 'Other',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewDetails')
                        ->label('View Full SMS')
                        ->icon(Heroicon::OutlinedEye)
                        ->modalWidth(Width::Medium)
                        ->modalHeading(fn (IncomingSms $record) => "SMS #{$record->id} - {$record->provider} ({$record->trx_id})")
                        ->modalContent(function (IncomingSms $record) {
                            return view('filament.pages.partials.incoming-sms-details', [
                                'record' => $record,
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
                ]),
            ]);
    }
}
