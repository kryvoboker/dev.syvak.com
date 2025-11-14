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
use UnitEnum;

class LanguageResource extends Resource
{
    protected static ?string                $model                = Language::class;
    protected static string|BackedEnum|null $navigationIcon       = Heroicon::Language;
    protected static string|null|UnitEnum   $navigationGroup      = 'Settings';
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
     * Get the navigation label for the resource.
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('admin/settings/language.navigation_label');
    }

    /**
     * Get the model label for the resource.
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('admin/settings/language.label_model');
    }

    /**
     * Get the plural model label for the resource.
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/settings/language.label_plural_model');
    }

    /**
     * Get the navigation group for the resource.
     *
     * @return string|null
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin/settings/language.navigation_group');
    }
}
