<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\CatalogFilter;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Catalogs\CatalogFilter\Pages\EditCatalogFilterSet;
use App\Filament\Resources\Catalogs\CatalogFilter\Schemas\CatalogFilterSetForm;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CatalogFilterSetResource extends Resource
{
    protected static ?string $model = CatalogFilterSet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AdjustmentsHorizontal;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Catalog;

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return CatalogFilterSetForm::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => EditCatalogFilterSet::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/catalogs/catalog-filter/catalog-filter-set.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/catalogs/catalog-filter/catalog-filter-set.labels.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/catalogs/catalog-filter/catalog-filter-set.labels.plural_model');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(CatalogFilterSet|Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
