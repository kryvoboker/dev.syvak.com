<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups\Tables;

use App\Models\Users\UserGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UserGroupsTable
{
    /**
     * @param Table $table
     *
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin/users/user_groups.column_name'))
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label(__('admin/users/user_groups.column_active'))
                    ->boolean(),

                IconColumn::make('is_default')
                    ->label(__('admin/users/user_groups.column_default'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('admin/users/user_groups.column_created_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, Collection $records) {
                            if ($records->contains('is_default', true)) {
                                Notification::make()
                                    ->title(__('admin/users/user_groups.text_cant_delete_default_user_group'))
                                    ->body(__('admin/users/user_groups.error_cant_delete_default_user_group'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }

                            // Check if trying to delete all active languages
                            $active_to_delete = $records->where('is_active', true)->count();
                            $total_active     = UserGroup::where('is_active', true)->count();

                            if ($active_to_delete >= $total_active) {
                                Notification::make()
                                    ->title(__('admin/users/user_groups.text_cant_delete_last_active_user_group'))
                                    ->body(__('admin/users/user_groups.error_cant_delete_last_active_user_group'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }
}
