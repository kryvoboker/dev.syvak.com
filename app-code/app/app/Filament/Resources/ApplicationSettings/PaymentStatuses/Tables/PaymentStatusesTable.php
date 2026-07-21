<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\PaymentStatuses\Tables;

use App\Exceptions\PaymentStatusInvariantException;
use App\Models\Payment\PaymentStatuses;
use App\Services\Payment\PaymentStatusManagementService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class PaymentStatusesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('descriptions'))
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin/default.columns.code'))
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('descriptions')
                    ->label(__('admin/settings/payment_statuses.columns.name'))
                    ->state(fn (PaymentStatuses $record): string => $record->descriptions
                        ->pluck('name')
                        ->filter()
                        ->implode(' / '))
                    ->limit(100),
                IconColumn::make('is_active')
                    ->label(__('admin/default.labels.is_active'))
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_default')
                    ->label(__('admin/default.labels.is_default'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin/default.columns.created_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/default.filters.active'))
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/default.filters.active_only'))
                    ->falseLabel(__('admin/default.filters.inactive_only')),
                TernaryFilter::make('is_default')
                    ->label(__('admin/default.filters.default'))
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/default.filters.default'))
                    ->falseLabel(__('admin/default.filters.default')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label(__('admin/settings/payment_statuses.actions.activate'))
                        ->icon(Heroicon::CheckCircle)
                        ->action(function (Collection $records): void {
                            self::runBulkAction(
                                __('admin/settings/payment_statuses.notifications.activated'),
                                function () use ($records): void {
                                    app(PaymentStatusManagementService::class)->activate($records);
                                },
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label(__('admin/settings/payment_statuses.actions.deactivate'))
                        ->icon(Heroicon::XCircle)
                        ->action(function (Collection $records): void {
                            self::runBulkAction(
                                __('admin/settings/payment_statuses.notifications.deactivated'),
                                function () use ($records): void {
                                    app(PaymentStatusManagementService::class)->deactivate($records);
                                },
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, Collection $records): void {
                            if ($records->contains('is_default', true)) {
                                self::sendInvariantNotification('cannot_delete_default');
                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    /**
     * @param \Closure(): void $callback
     */
    private static function runBulkAction(string $success_message, \Closure $callback): void
    {
        try {
            $callback();

            Notification::make()
                ->title($success_message)
                ->success()
                ->send();
        } catch (PaymentStatusInvariantException $exception) {
            self::sendInvariantNotification($exception->reason);
        } catch (Throwable $throwable) {
            report($throwable);

            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/settings/payment_statuses.errors.operation_failed'))
                ->danger()
                ->send();
        }
    }

    private static function sendInvariantNotification(string $reason): void
    {
        Notification::make()
            ->title(__('admin/default.errors.title'))
            ->body(__('admin/settings/payment_statuses.errors.' . $reason))
            ->danger()
            ->send();
    }
}
