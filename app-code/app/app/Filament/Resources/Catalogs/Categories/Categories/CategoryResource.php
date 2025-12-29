<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Catalogs\Categories\Categories\Pages\CreateCategory;
use App\Filament\Resources\Catalogs\Categories\Categories\Pages\EditCategory;
use App\Filament\Resources\Catalogs\Categories\Categories\Pages\ListCategories;
use App\Filament\Resources\Catalogs\Categories\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Catalogs\Categories\Categories\Tables\CategoriesTable;
use App\Filament\Resources\Trait\TotalItemsTrait;
use App\Models\Catalogs\Categories\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    use TotalItemsTrait;

    protected static ?string                $model           = Category::class;
    protected static string|BackedEnum|null $navigationIcon  = Heroicon::Bookmark;
    protected static string|null|UnitEnum   $navigationGroup = AdminNavigationGroupEnum::Catalog;

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
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
            'index'  => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit'   => EditCategory::route('/{record}/edit'),
        ];
    }

    /**
     * Signature in the navigation menu (left panel)
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('admin/catalogs/categories/categories.navigation_label');
    }

    /**
     * A single model name (e.g. in headings, "Create X" button)
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('admin/catalogs/categories/categories.labels.model');
    }

    /**
     * Plural model name (e.g. in lists, section headings)
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/catalogs/categories/categories.labels.plural_model');
    }
}
