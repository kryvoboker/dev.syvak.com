<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleDefinitions;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Modules\ModuleDefinitions\Pages\EditModuleDefinition;
use App\Filament\Resources\Modules\ModuleDefinitions\Pages\ListModuleDefinitions;
use App\Filament\Resources\Modules\ModuleDefinitions\RelationManagers\ModuleInstancesRelationManager;
use App\Filament\Resources\Modules\ModuleDefinitions\Schemas\ModuleDefinitionForm;
use App\Filament\Resources\Modules\ModuleDefinitions\Tables\ModuleDefinitionsTable;
use App\Models\Modules\ModuleDefinition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ModuleDefinitionResource extends Resource
{
    protected static ?string $model = ModuleDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Modules;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ModuleDefinitionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ModuleDefinitionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ModuleInstancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModuleDefinitions::route('/'),
            'edit'  => EditModuleDefinition::route('/{record}/edit'),
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

    public static function canDelete(ModuleDefinition|Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
