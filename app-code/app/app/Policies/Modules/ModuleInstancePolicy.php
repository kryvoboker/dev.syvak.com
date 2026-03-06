<?php

declare(strict_types=1);

namespace App\Policies\Modules;

use App\Models\Modules\ModuleInstance;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ModuleInstancePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ModuleInstance');
    }

    public function view(AuthUser $authUser, ModuleInstance $module_instance): bool
    {
        return $authUser->can('View:ModuleInstance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ModuleInstance');
    }

    public function update(AuthUser $authUser, ModuleInstance $module_instance): bool
    {
        return $authUser->can('Update:ModuleInstance');
    }

    public function delete(AuthUser $authUser, ModuleInstance $module_instance): bool
    {
        return $authUser->can('Delete:ModuleInstance');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ModuleInstance');
    }

    public function replicate(AuthUser $authUser, ModuleInstance $module_instance): bool
    {
        return $authUser->can('Replicate:ModuleInstance');
    }
}
