<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Languages;

use App\Filament\Resources\Settings\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Settings\Languages\Pages\EditLanguage;
use App\Filament\Resources\Settings\Languages\Pages\ListLanguages;
use App\Filament\Resources\Settings\Languages\Schemas\LanguageForm;
use App\Filament\Resources\Settings\Languages\Tables\LanguagesTable;
use App\Models\Settings\Language;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LanguageResource extends Resource
{
    protected static ?string                $model                = Language::class;
    protected static string|BackedEnum|null $navigationIcon       = Heroicon::Language;
    protected static ?string                $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LanguageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LanguagesTable::configure($table);
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
            'index'  => ListLanguages::route('/'),
            'create' => CreateLanguage::route('/create'),
            'edit'   => EditLanguage::route('/{record}/edit'),
        ];
    }

    /**
     * Signature in the navigation menu (left panel)
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('admin/settings/language.navigation_label');
    }

    /**
     * A single model name (e.g. in headings, "Create X" button)
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('admin/settings/language.label_model');
    }

    /**
     * Plural model name (e.g. in lists, section headings)
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/settings/language.label_plural_model');
    }

    /**
     * For the name of the parent menu item for the menu group
     *
     * @return string|null
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin/default.menu_item_settings');
    }
}
