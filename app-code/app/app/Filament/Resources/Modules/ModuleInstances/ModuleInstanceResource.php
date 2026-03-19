<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleInstances;

use App\Filament\Resources\Modules\ModuleInstances\Pages\CreateModuleInstance;
use App\Filament\Resources\Modules\ModuleInstances\Pages\EditModuleInstance;
use App\Filament\Resources\Modules\ModuleInstances\Pages\ListModuleInstances;
use App\Filament\Resources\Modules\ModuleInstances\Schemas\ModuleInstanceForm;
use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Hidden resource that owns module settings create and edit pages.
 *
 * Filament still needs a resource to build URLs, resolve permissions, and bind
 * the shared schema, but the list screen itself lives on ModuleDefinitionResource.
 */
class ModuleInstanceResource extends Resource
{
    protected static ?string $model = ModuleInstance::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ModuleInstanceForm::configure(
            $schema,
            static::resolveDefinitionFromRequest(app(Request::class)),
            static::resolveInstanceFromRequest(app(Request::class)),
        );
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListModuleInstances::route('/'),
            'create' => CreateModuleInstance::route('/create'),
            'edit'   => EditModuleInstance::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('admin/modules/module_instances.labels.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/modules/module_instances.labels.plural_model');
    }

    public static function canViewAny(): bool
    {
        $auth_user = auth()->user();

        if ($auth_user === null) {
            return false;
        }

        return $auth_user->can('ViewAny:ModuleInstance') || $auth_user->can('ViewAny:ModuleDefinition');
    }

    public static function canCreate(): bool
    {
        $auth_user = auth()->user();

        if ($auth_user === null) {
            return false;
        }

        return $auth_user->can('Create:ModuleInstance') || $auth_user->can('Update:ModuleDefinition');
    }

    public static function canEdit(Model $record): bool
    {
        $auth_user = auth()->user();

        if ($auth_user === null) {
            return false;
        }

        return $auth_user->can('Update:ModuleInstance') || $auth_user->can('Update:ModuleDefinition');
    }

    public static function canDelete(Model $record): bool
    {
        $auth_user = auth()->user();

        if ($auth_user === null) {
            return false;
        }

        return $auth_user->can('Delete:ModuleInstance') || $auth_user->can('Update:ModuleDefinition');
    }

    public static function resolveDefinitionFromRequest(Request $request): ?ModuleDefinition
    {
        $instance = static::resolveInstanceFromRequest($request);

        if ($instance !== null) {
            return $instance->definition;
        }

        $definition_id = $request->integer('definition');

        if ($definition_id < 1) {
            $definition_id = self::resolveDefinitionIdFromReferer($request);
        }

        if ($definition_id < 1) {
            return null;
        }

        return ModuleDefinition::query()->find($definition_id);
    }

    public static function resolveInstanceFromRequest(Request $request): ?ModuleInstance
    {
        $record = $request->route('record');

        if ($record instanceof ModuleInstance) {
            return $record->loadMissing('definition');
        }

        if (! is_scalar($record)) {
            return null;
        }

        return ModuleInstance::query()
            ->with('definition')
            ->find((int) $record);
    }

    private static function resolveDefinitionIdFromReferer(Request $request): int
    {
        $referer_url   = (string) $request->headers->get('referer', '');
        $referer_query = parse_url($referer_url, PHP_URL_QUERY);

        if (! is_string($referer_query) || blank($referer_query)) {
            return 0;
        }

        parse_str($referer_query, $query_params);

        return (int) data_get($query_params, 'definition', 0);
    }
}
