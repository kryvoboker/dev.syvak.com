<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Catalogs\Products\Products\Pages\CreateProduct;
use App\Filament\Resources\Catalogs\Products\Products\Pages\EditProduct;
use App\Filament\Resources\Catalogs\Products\Products\Pages\ListProducts;
use App\Filament\Resources\Catalogs\Products\Products\Schemas\ProductForm;
use App\Filament\Resources\Catalogs\Products\Products\Tables\ProductsTable;
use App\Filament\Resources\Trait\TotalItemsTrait;
use App\Models\Catalogs\Products\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductResource extends Resource
{
    use TotalItemsTrait;

    protected static ?string                $model           = Product::class;
    protected static string|BackedEnum|null $navigationIcon  = Heroicon::ShoppingCart;
    protected static string|null|UnitEnum   $navigationGroup = AdminNavigationGroupEnum::Catalog;

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
            'index'  => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit'   => EditProduct::route('/{record}/edit'),
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
        return __('admin/catalogs/products/products.labels.model');
    }

    /**
     * Plural model name (e.g. in lists, section headings)
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/catalogs/products/products.labels.plural_model');
    }
}
