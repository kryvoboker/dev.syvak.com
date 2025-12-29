<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Trait\TotalModelItemsTrait;
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
use UnitEnum;

class UserGroupResource extends Resource
{
    use TotalModelItemsTrait;

    protected static ?string                $model                = UserGroup::class;
    protected static string|BackedEnum|null $navigationIcon       = Heroicon::UserGroup;
    protected static ?string                $recordTitleAttribute = 'name';
    protected static string|null|UnitEnum   $navigationGroup      = AdminNavigationGroupEnum::Users;

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
            'index'  => ListUserGroups::route('/'),
            'create' => CreateUserGroup::route('/create'),
            'edit'   => EditUserGroup::route('/{record}/edit'),
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
        return __('admin/users/user_groups.labels.model');
    }

    /**
     * Plural model name (e.g. in lists, section headings)
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/users/user_groups.labels.plural_model');
    }
}
