<?php

namespace App\Filament\Resources\Settings\AppSettings;

use App\Filament\Resources\Settings\AppSettings\Pages\CreateAppSetting;
use App\Filament\Resources\Settings\AppSettings\Pages\EditAppSetting;
use App\Filament\Resources\Settings\AppSettings\Pages\ListAppSettings;
use App\Filament\Resources\Settings\AppSettings\Schemas\AppSettingForm;
use App\Filament\Resources\Settings\AppSettings\Tables\AppSettingsTable;
use App\Models\Settings\AppSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AppSettingResource extends Resource
{
    protected static ?string                $model                = AppSetting::class;
    protected static string|null|UnitEnum   $navigationGroup      = 'Settings';
    protected static string|BackedEnum|null $navigationIcon       = Heroicon::Cog6Tooth;
    protected static ?string                $recordTitleAttribute = 'app_settings';

    public static function form(Schema $schema): Schema
    {
        return AppSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppSettingsTable::configure($table);
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
            'index'  => ListAppSettings::route('/'),
            'create' => CreateAppSetting::route('/create'),
            'edit'   => EditAppSetting::route('/{record}/edit'),
        ];
    }
}
