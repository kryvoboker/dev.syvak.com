<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Users\Tables;

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
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Storage;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $user_image_settings     = self::resolveUserImageSettings();
        $user_preview_list_width = max(1, (int) data_get($user_image_settings, 'preview_in_list_in_admin.width', (int) config('app.images.user.preview_in_list_in_admin.width')));
        $user_no_image_path      = (string) data_get($user_image_settings, 'no_image', (string) config('app.images.user.no_image'));

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin/default.columns.name'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('lastname')
                    ->label(__('admin/default.columns.lastname'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('email')
                    ->label(__('admin/default.columns.email'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('telephone')
                    ->label(__('admin/default.columns.telephone'))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->formatStateUsing(function ($state) {
                        return parse_telephone($state);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('avatar')
                    ->label(__('admin/default.columns.avatar'))
                    ->imageSize($user_preview_list_width)
                    ->circular()
                    ->checkFileExistence()
                    ->defaultImageUrl(Storage::url($user_no_image_path))
                    ->extraImgAttributes([
                        'decoding' => 'async',
                        'loading'  => 'lazy',
                        'style'    => 'object-fit: contain;',
                    ]),

                IconColumn::make('is_active')
                    ->label(__('admin/default.labels.is_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('email_verified_at')
                    ->label(__('admin/default.columns.email_verified_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('admin/default.columns.created_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/default.filters.active'))
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/default.filters.active_only'))
                    ->falseLabel(__('admin/default.filters.inactive_only')),
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
                                    ->title(__('admin/default.errors.title'))
                                    ->body(__('admin/users/users.errors.cant_delete_special_user'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolveUserImageSettings(): array
    {
        $app_settings = get_app_settings();
        $settings     = data_get($app_settings, 'user_settings');

        if ($settings instanceof SupportCollection) {
            return $settings->toArray();
        }

        if (is_array($settings)) {
            return $settings;
        }

        return [];
    }
}
