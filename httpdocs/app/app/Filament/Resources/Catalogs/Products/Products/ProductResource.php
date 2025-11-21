<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products;

use App\Filament\Resources\Catalogs\Products\Products\Pages\CreateProduct;
use App\Filament\Resources\Catalogs\Products\Products\Pages\EditProduct;
use App\Filament\Resources\Catalogs\Products\Products\Pages\ListProducts;
use App\Filament\Resources\Catalogs\Products\Products\Schemas\ProductForm;
use App\Filament\Resources\Catalogs\Products\Products\Tables\ProductsTable;
use App\Models\Catalogs\Products\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingCart;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    /**
     * Signature in the navigation menu (left panel)
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('admin/catalogs/products/products.navigation_label');
    }

    /**
     * A single model name (e.g. in headings, "Create X" button)
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('admin/catalogs/products/products.label_model');
    }

    /**
     * Plural model name (e.g. in lists, section headings)
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/catalogs/products/products.label_plural_model');
    }

    /**
     * For the name of the parent menu item for the menu group
     *
     * @return string|null
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin/default.menu_item_catalog');
    }
}
