<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Infos\InfoPages\Pages\CreateInfoPage;
use App\Filament\Resources\Infos\InfoPages\Pages\EditInfoPage;
use App\Filament\Resources\Infos\InfoPages\Pages\ListInfoPages;
use App\Filament\Resources\Infos\InfoPages\Schemas\InfoPageForm;
use App\Filament\Resources\Infos\InfoPages\Tables\InfoPagesTable;
use App\Filament\Resources\Trait\TotalModelItemsResourceTrait;
use App\Models\Infos\InfoPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InfoPageResource extends Resource
{
    use TotalModelItemsResourceTrait;

    protected static ?string $model = InfoPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::InformationCircle;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::InfoPages;

    public static function form(Schema $schema): Schema
    {
        return InfoPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InfoPagesTable::configure($table);
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
            'index' => ListInfoPages::route('/'),
            'create' => CreateInfoPage::route('/create'),
            'edit' => EditInfoPage::route('/{record}/edit'),
        ];
    }

    /**
     * Signature in the navigation menu (left panel)
     */
    public static function getNavigationLabel(): string
    {
        return __('admin/infos/info_pages.navigation_label');
    }

    /**
     * A single model name (e.g. in headings, "Create X" button)
     */
    public static function getModelLabel(): string
    {
        return __('admin/infos/info_pages.labels.model');
    }

    /**
     * Plural model name (e.g. in lists, section headings)
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/infos/info_pages.labels.plural_model');
    }
}
