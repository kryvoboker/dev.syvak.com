<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Search;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\PageSettings\Search\Pages\EditSearchPageSettings;
use App\Filament\Resources\PageSettings\Search\Schemas\SearchPageSettingsForm;
use App\Models\PageSettings\PageSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SearchPageSettingResource extends Resource
{
    protected static ?string $model = PageSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MagnifyingGlass;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::PageSettings;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return SearchPageSettingsForm::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => EditSearchPageSettings::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/settings/search_page_settings.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/settings/search_page_settings.labels.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/settings/search_page_settings.labels.plural_model');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(PageSetting|Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
