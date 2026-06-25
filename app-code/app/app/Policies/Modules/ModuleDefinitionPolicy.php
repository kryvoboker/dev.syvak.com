<?php

declare(strict_types=1);

namespace App\Policies\Modules;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ModuleDefinitionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ViewAny:ModuleDefinition');
    }

    public function view(AuthUser $auth_user, ModuleDefinition $module_definition): bool
    {
        unset($module_definition);

        return $auth_user->can('View:ModuleDefinition');
    }

    public function create(AuthUser $auth_user): bool
    {
        return $auth_user->can('Create:ModuleDefinition');
    }

    public function update(AuthUser $auth_user, ModuleDefinition $module_definition): bool
    {
        unset($module_definition);

        return $auth_user->can('Update:ModuleDefinition');
    }

    public function delete(AuthUser $auth_user, ModuleDefinition $module_definition): bool
    {
        unset($module_definition);

        return $auth_user->can('Delete:ModuleDefinition');
    }

    public function deleteAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('DeleteAny:ModuleDefinition');
    }

    public function replicate(AuthUser $auth_user, ModuleDefinition $module_definition): bool
    {
        unset($module_definition);

        return $auth_user->can('Replicate:ModuleDefinition');
    }
}
