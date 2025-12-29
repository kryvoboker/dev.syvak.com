<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\AppSettings;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Settings\AppSettings\Pages\EditAppSetting;
use App\Filament\Resources\Settings\AppSettings\Schemas\AppSettingForm;
use App\Models\Settings\AppSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AppSettingResource extends Resource
{
    protected static ?string                $model                = AppSetting::class;
    protected static string|BackedEnum|null $navigationIcon       = Heroicon::Cog6Tooth;
    protected static string|null|UnitEnum   $navigationGroup      = AdminNavigationGroupEnum::Settings;
    protected static ?string                $recordTitleAttribute = 'timezone';
    protected static ?int                   $navigationSort       = 99;

    /**
     * Should register navigation
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    /**
     * Get navigation URL
     *
     * @return string
     */
    public static function getNavigationUrl(): string
    {
        return static::getUrl('index');
    }

    public static function form(Schema $schema): Schema
    {
        return AppSettingForm::configure($schema);
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
            'index' => EditAppSetting::route('/'),
        ];
    }

    /**
     * Signature in the navigation menu (left panel)
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('admin/settings/app_settings.navigation_label');
    }

    /**
     * A single model name (e.g. in headings, "Create X" button)
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('admin/settings/app_settings.labels.model');
    }

    /**
     * Plural model name (e.g. in lists, section headings)
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/settings/app_settings.labels.plural_model');
    }

    /**
     * Can create records
     *
     * @return bool
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Can delete records
     *
     * @param AppSetting|Model $record
     *
     * @return bool
     */
    public static function canDelete(AppSetting|Model $record): bool
    {
        return false;
    }

    /**
     * Can delete any records
     *
     * @return bool
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }
}
