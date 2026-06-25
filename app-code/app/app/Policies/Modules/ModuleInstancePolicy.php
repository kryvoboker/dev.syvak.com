<?php

declare(strict_types=1);

namespace App\Policies\Modules;

use App\Models\Modules\ModuleInstance;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ModuleInstancePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ViewAny:ModuleInstance');
    }

    public function view(AuthUser $auth_user, ModuleInstance $module_instance): bool
    {
        unset($module_instance);

        return $auth_user->can('View:ModuleInstance');
    }

    public function create(AuthUser $auth_user): bool
    {
        return $auth_user->can('Create:ModuleInstance');
    }

    public function update(AuthUser $auth_user, ModuleInstance $module_instance): bool
    {
        unset($module_instance);

        return $auth_user->can('Update:ModuleInstance');
    }

    public function delete(AuthUser $auth_user, ModuleInstance $module_instance): bool
    {
        unset($module_instance);

        return $auth_user->can('Delete:ModuleInstance');
    }

    public function deleteAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('DeleteAny:ModuleInstance');
    }

    public function replicate(AuthUser $auth_user, ModuleInstance $module_instance): bool
    {
        unset($module_instance);

        return $auth_user->can('Replicate:ModuleInstance');
    }
}
