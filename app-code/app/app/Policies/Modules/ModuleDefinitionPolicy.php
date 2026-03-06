<?php

declare(strict_types=1);

namespace App\Policies\Modules;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ModuleDefinitionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ModuleDefinition');
    }

    public function view(AuthUser $authUser, ModuleDefinition $module_definition): bool
    {
        return $authUser->can('View:ModuleDefinition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ModuleDefinition');
    }

    public function update(AuthUser $authUser, ModuleDefinition $module_definition): bool
    {
        return $authUser->can('Update:ModuleDefinition');
    }

    public function delete(AuthUser $authUser, ModuleDefinition $module_definition): bool
    {
        return $authUser->can('Delete:ModuleDefinition');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ModuleDefinition');
    }

    public function replicate(AuthUser $authUser, ModuleDefinition $module_definition): bool
    {
        return $authUser->can('Replicate:ModuleDefinition');
    }
}
