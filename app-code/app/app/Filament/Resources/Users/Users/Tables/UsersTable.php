<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Users\Tables;

use App\Filament\Resources\Trait\Tables\BooleanTableTrait;
use App\Filament\Resources\Trait\Tables\CommonTextTableTrait;
use App\Filament\Resources\Trait\Tables\DateTableTrait;
use App\Filament\Resources\Trait\Tables\ImageTableTrait;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class UsersTable
{
    use CommonTextTableTrait, BooleanTableTrait, DateTableTrait, ImageTableTrait;

    /**
     * @param Table $table
     *
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                self::getNameTableField(),

                TextColumn::make('lastname')
                    ->label(__('admin/default.columns.lastname'))
                    ->searchable(),

                TextColumn::make('email')
                    ->label(__('admin/default.columns.email'))
                    ->searchable(),

                TextColumn::make('telephone')
                    ->label(__('admin/default.columns.telephone'))
                    ->formatStateUsing(function ($state) {
                        return parse_telephone($state);
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                self::getImageTableField([
                    'field_name'        => 'avatar',
                    'label'             => __('admin/default.columns.avatar'),
                    'image_size'        => (int)config('app.images.user.preview_in_list_in_admin.width'),
                    'circular'          => true,
                    'default_image_url' => Storage::url(config('app.images.user.no_image')),
                ]),

                self::getIsActiveTableField(),

                self::getEmailVerifiedAtTableField(),

                self::getCreatedAtTableField(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/default.filters.active'))
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
}
