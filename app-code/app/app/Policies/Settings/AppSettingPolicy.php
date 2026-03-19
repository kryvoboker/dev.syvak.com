<?php

declare(strict_types=1);

namespace App\Policies\Settings;

use App\Models\ApplicationSettings\AppSetting;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AppSettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AppSetting');
    }

    public function view(AuthUser $authUser, AppSetting $appSetting): bool
    {
        return $authUser->can('View:AppSetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AppSetting');
    }

    public function update(AuthUser $authUser, AppSetting $appSetting): bool
    {
        return $authUser->can('Update:AppSetting');
    }

    public function delete(AuthUser $authUser, AppSetting $appSetting): bool
    {
        return $authUser->can('Delete:AppSetting');
    }

    public function restore(AuthUser $authUser, AppSetting $appSetting): bool
    {
        return $authUser->can('Restore:AppSetting');
    }

    public function forceDelete(AuthUser $authUser, AppSetting $appSetting): bool
    {
        return $authUser->can('ForceDelete:AppSetting');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AppSetting');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AppSetting');
    }

    public function replicate(AuthUser $authUser, AppSetting $appSetting): bool
    {
        return $authUser->can('Replicate:AppSetting');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AppSetting');
    }
}
