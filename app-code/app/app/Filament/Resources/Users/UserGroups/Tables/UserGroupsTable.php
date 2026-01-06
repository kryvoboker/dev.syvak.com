<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups\Tables;

use App\Filament\Resources\Trait\Tables\BooleanTableTrait;
use App\Filament\Resources\Trait\Tables\CommonTextTableTrait;
use App\Filament\Resources\Trait\Tables\DateTableTrait;
use App\Models\Users\UserGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UserGroupsTable
{
    use CommonTextTableTrait, BooleanTableTrait, DateTableTrait;

    /**
     * @param Table $table
     *
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                self::getTextTableField([
                    'filed_name' => 'name',
                    'label'      => __('admin/default.columns.name'),
                ]),

                self::getIsActiveTableField(),

                self::getIsDefaultTableField(),

                self::getCreatedAtTableField(),

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
                                    ->title(__('admin/default.errors.title'))
                                    ->body(__('admin/users/user_groups.errors.cant_delete_default_user_group'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }

                            // Check if trying to delete all active languages
                            $active_to_delete = $records->where('is_active', true)->count();
                            $total_active     = UserGroup::where('is_active', true)->count();

                            if ($active_to_delete >= $total_active) {
                                Notification::make()
                                    ->title(__('admin/default.errors.title'))
                                    ->body(__('admin/users/user_groups.errors.cant_delete_last_active_user_group'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }
}
