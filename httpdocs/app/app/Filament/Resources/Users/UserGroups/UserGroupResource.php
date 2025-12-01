<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups;

use App\Filament\Resources\Users\UserGroups\Pages\CreateUserGroup;
use App\Filament\Resources\Users\UserGroups\Pages\EditUserGroup;
use App\Filament\Resources\Users\UserGroups\Pages\ListUserGroups;
use App\Filament\Resources\Users\UserGroups\Schemas\UserGroupForm;
use App\Filament\Resources\Users\UserGroups\Tables\UserGroupsTable;
use App\Models\Users\UserGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserGroupResource extends Resource
{
    protected static ?string $model = UserGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserGroups::route('/'),
            'create' => CreateUserGroup::route('/create'),
            'edit' => EditUserGroup::route('/{record}/edit'),
        ];
    }

    /**
     * Signature in the navigation menu (left panel)
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('admin/users/user_groups.navigation_label');
    }

    /**
     * A single model name (e.g. in headings, "Create X" button)
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('admin/users/user_groups.label_model');
    }

    /**
     * Plural model name (e.g. in lists, section headings)
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/users/user_groups.label_plural_model');
    }

    /**
     * For the name of the parent menu item for the menu group
     *
     * @return string|null
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin/default.menu.item_users');
    }
}
