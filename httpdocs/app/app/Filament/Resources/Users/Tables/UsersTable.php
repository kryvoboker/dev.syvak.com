<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin/users/users.column_name'))
                    ->searchable(),

                TextColumn::make('lastname')
                    ->label(__('admin/users/users.column_lastname'))
                    ->searchable(),

                TextColumn::make('email')
                    ->label(__('admin/users/users.column_email'))
                    ->searchable(),

                TextColumn::make('telephone')
                    ->label(__('admin/users/users.column_telephone'))
                    ->formatStateUsing(function ($state) {
                        return parse_telephone($state);
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('avatar')
                    ->label(__('admin/users/users.column_avatar')),

                IconColumn::make('is_active')
                    ->label(__('admin/users/users.column_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('email_verified_at')
                    ->label(__('admin/users/users.column_email_verified_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('admin/users/users.column_created_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/users/users.filter_active'))
                    ->placeholder(__('admin/users/users.placeholder_all'))
                    ->trueLabel(__('admin/users/users.true_label_active_only'))
                    ->falseLabel(__('admin/users/users.false_label_inactive_only')),

                TernaryFilter::make('name')
                    ->label(__('admin/users/users.filter_name'))
                    ->placeholder(__('admin/users/users.placeholder_all'))
                    ->trueLabel(__('admin/users/users.true_label_name_only'))
                    ->falseLabel(__('admin/users/users.false_label_name_excluded')),

                TernaryFilter::make('lastname')
                    ->label(__('admin/users/users.filter_lastname'))
                    ->placeholder(__('admin/users/users.placeholder_all'))
                    ->trueLabel(__('admin/users/users.true_label_lastname_only'))
                    ->falseLabel(__('admin/users/users.false_label_lastname_excluded')),

                TernaryFilter::make('email')
                    ->label(__('admin/users/users.filter_email'))
                    ->placeholder(__('admin/users/users.placeholder_all'))
                    ->trueLabel(__('admin/users/users.true_label_email_only'))
                    ->falseLabel(__('admin/users/users.false_label_email_excluded')),

                TernaryFilter::make('telephone')
                    ->label(__('admin/users/users.filter_telephone'))
                    ->placeholder(__('admin/users/users.placeholder_all'))
                    ->trueLabel(__('admin/users/users.true_label_telephone_only'))
                    ->falseLabel(__('admin/users/users.false_label_telephone_excluded')),

                TernaryFilter::make('email_verified_at')
                    ->label(__('admin/users/users.filter_email_verified_at'))
                    ->placeholder(__('admin/users/users.placeholder_all'))
                    ->trueLabel(__('admin/users/users.true_label_email_verified_only'))
                    ->falseLabel(__('admin/users/users.false_label_email_not_verified_only')),

                TernaryFilter::make('created_at')
                    ->label(__('admin/users/users.filter_created_at'))
                    ->placeholder(__('admin/users/users.placeholder_all'))
                    ->trueLabel(__('admin/users/users.true_label_created_only')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, Collection $records) {
                            $denied_emails = config('app.denied_delete_emails', []);

                            // Check if any selected record has an email in denied list
                            if ($records->pluck('email')->intersect($denied_emails)->isNotEmpty()) {
                                Notification::make()
                                    ->title(__('admin/settings/language.text_cant_delete_special_user'))
                                    ->body(__('admin/settings/language.error_cant_delete_special_user'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }
}
