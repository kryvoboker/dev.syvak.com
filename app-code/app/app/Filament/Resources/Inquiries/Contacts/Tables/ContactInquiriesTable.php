<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inquiries\Contacts\Tables;

use App\Enums\Inquiries\InquiryStatusEnum;
use App\Models\Inquiries\Inquiry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactInquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                /** @var Builder<Inquiry> $query */
                return $query->newestFirst();
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin/inquiries/contacts.labels.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('admin/inquiries/contacts.labels.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('admin/inquiries/contacts.labels.phone'))
                    ->searchable(),
                TextColumn::make('inquiryable.message')
                    ->label(__('admin/inquiries/contacts.labels.message'))
                    ->state(fn (Inquiry $record): string => (string) data_get($record->inquiryable, 'message', ''))
                    ->limit(80),
                TextColumn::make('status')
                    ->label(__('admin/inquiries/contacts.labels.status'))
                    ->formatStateUsing(fn (InquiryStatusEnum|string $state): string => __('admin/inquiries/contacts.statuses.' . ($state instanceof InquiryStatusEnum ? $state->value : $state)))
                    ->badge()
                    ->sortable(),
                IconColumn::make('attachments')
                    ->label(__('admin/inquiries/contacts.labels.attachment'))
                    ->state(fn (Inquiry $record): bool => $record->attachments->isNotEmpty())
                    ->boolean(),
                TextColumn::make('submitted_at')
                    ->label(__('admin/inquiries/contacts.labels.submitted_at'))
                    ->dateTime(config('app.datetime_format'), config('app.timezone'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin/inquiries/contacts.filters.status'))
                    ->options(self::statusOptions()),
                Filter::make('submitted_at')
                    ->label(__('admin/inquiries/contacts.filters.date'))
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('admin/inquiries/contacts.filters.date_from')),
                        DatePicker::make('until')
                            ->label(__('admin/inquiries/contacts.filters.date_until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $date_query, string $date): Builder => $date_query->whereDate('submitted_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $date_query, string $date): Builder => $date_query->whereDate('submitted_at', '<=', $date))),
                TrashedFilter::make()
                    ->label(__('admin/inquiries/contacts.filters.deletion_status'))
                    ->placeholder(__('admin/inquiries/contacts.filters.not_deleted'))
                    ->trueLabel(__('admin/inquiries/contacts.filters.all'))
                    ->falseLabel(__('admin/inquiries/contacts.filters.deleted_only')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    RestoreBulkAction::make()
                        ->requiresConfirmation(),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        return collect(InquiryStatusEnum::cases())
            ->mapWithKeys(fn (InquiryStatusEnum $status): array => [
                $status->value => __('admin/inquiries/contacts.statuses.' . $status->value),
            ])
            ->all();
    }
}
