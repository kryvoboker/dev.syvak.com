<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleDefinitions;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Modules\ModuleDefinitions\Pages\ListModuleDefinitions;
use App\Models\Modules\ModuleDefinition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Navigation resource for the grouped modules screen.
 *
 * Editors do not manage module definitions directly, so this resource exists
 * only to register the sidebar entry and route the custom list page.
 */
class ModuleDefinitionResource extends Resource
{
    protected static ?string $model = ModuleDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Modules;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 10;

    public static function getPages(): array
    {
        return [
            'index' => ListModuleDefinitions::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/modules/module_definitions.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/modules/module_definitions.labels.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/modules/module_definitions.labels.plural_model');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
