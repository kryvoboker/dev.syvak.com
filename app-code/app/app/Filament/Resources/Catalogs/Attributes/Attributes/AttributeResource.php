<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Catalogs\Attributes\Attributes\Pages\CreateAttribute;
use App\Filament\Resources\Catalogs\Attributes\Attributes\Pages\EditAttribute;
use App\Filament\Resources\Catalogs\Attributes\Attributes\Pages\ListAttributes;
use App\Filament\Resources\Catalogs\Attributes\Attributes\Schemas\AttributeForm;
use App\Filament\Resources\Catalogs\Attributes\Attributes\Tables\AttributesTable;
use App\Filament\Resources\Trait\TotalModelItemsResourceTrait;
use App\Models\Catalogs\Attributes\Attribute;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AttributeResource extends Resource
{
    use TotalModelItemsResourceTrait;

    protected static ?string                $model           = Attribute::class;
    protected static string|BackedEnum|null $navigationIcon  = Heroicon::Tag;
    protected static string|null|UnitEnum   $navigationGroup = AdminNavigationGroupEnum::Catalog;

    public static function form(Schema $schema): Schema
    {
        return AttributeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttributesTable::configure($table);
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
            'index'  => ListAttributes::route('/'),
            'create' => CreateAttribute::route('/create'),
            'edit'   => EditAttribute::route('/{record}/edit'),
        ];
    }

    /**
     * Signature in the navigation menu (left panel)
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('admin/catalogs/attributes/attributes.navigation_label');
    }

    /**
     * A single model name (e.g. in headings, "Create X" button)
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('admin/catalogs/attributes/attributes.labels.model');
    }

    /**
     * Plural model name (e.g. in lists, section headings)
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/catalogs/attributes/attributes.labels.plural_model');
    }
}
