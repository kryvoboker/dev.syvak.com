<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products;

use App\Filament\Resources\Catalogs\Products\Products\Pages\CreateProductVariant;
use App\Filament\Resources\Catalogs\Products\Products\Pages\EditProductVariant;
use App\Filament\Resources\Catalogs\Products\Products\Pages\ListProductVariants;
use App\Filament\Resources\Catalogs\Products\Products\Schemas\ProductVariantForm;
use App\Models\Catalogs\Products\ProductVariant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static string|null|UnitEnum $navigationGroup = null;

    public static function form(Schema $schema): Schema
    {
        return ProductVariantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductVariants::route('/'),
            'create' => CreateProductVariant::route('/create'),
            'edit' => EditProductVariant::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('admin/catalogs/products/products.labels.variant_model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/catalogs/products/products.labels.variant_plural_model');
    }

    public static function canViewAny(): bool
    {
        return ProductResource::canViewAny();
    }

    public static function canCreate(): bool
    {
        return ProductResource::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return ProductResource::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return ProductResource::canViewAny();
    }
}
