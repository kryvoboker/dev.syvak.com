<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ViewAny:Role');
    }

    public function view(AuthUser $auth_user, Role $role): bool
    {
        unset($role);

        return $auth_user->can('View:Role');
    }

    public function create(AuthUser $auth_user): bool
    {
        return $auth_user->can('Create:Role');
    }

    public function update(AuthUser $auth_user, Role $role): bool
    {
        unset($role);

        return $auth_user->can('Update:Role');
    }

    public function delete(AuthUser $auth_user, Role $role): bool
    {
        unset($role);

        return $auth_user->can('Delete:Role');
    }

    public function restore(AuthUser $auth_user, Role $role): bool
    {
        unset($role);

        return $auth_user->can('Restore:Role');
    }

    public function forceDelete(AuthUser $auth_user, Role $role): bool
    {
        unset($role);

        return $auth_user->can('ForceDelete:Role');
    }

    public function forceDeleteAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ForceDeleteAny:Role');
    }

    public function restoreAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('RestoreAny:Role');
    }

    public function replicate(AuthUser $auth_user, Role $role): bool
    {
        unset($role);

        return $auth_user->can('Replicate:Role');
    }

    public function reorder(AuthUser $auth_user): bool
    {
        return $auth_user->can('Reorder:Role');
    }
}
