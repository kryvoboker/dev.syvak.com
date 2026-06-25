<?php

declare(strict_types=1);

namespace App\Policies\Settings;

use App\Models\ApplicationSettings\AppSetting;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AppSettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ViewAny:AppSetting');
    }

    public function view(AuthUser $auth_user, AppSetting $app_setting): bool
    {
        unset($app_setting);

        return $auth_user->can('View:AppSetting');
    }

    public function create(AuthUser $auth_user): bool
    {
        return $auth_user->can('Create:AppSetting');
    }

    public function update(AuthUser $auth_user, AppSetting $app_setting): bool
    {
        unset($app_setting);

        return $auth_user->can('Update:AppSetting');
    }

    public function delete(AuthUser $auth_user, AppSetting $app_setting): bool
    {
        unset($app_setting);

        return $auth_user->can('Delete:AppSetting');
    }

    public function restore(AuthUser $auth_user, AppSetting $app_setting): bool
    {
        unset($app_setting);

        return $auth_user->can('Restore:AppSetting');
    }

    public function forceDelete(AuthUser $auth_user, AppSetting $app_setting): bool
    {
        unset($app_setting);

        return $auth_user->can('ForceDelete:AppSetting');
    }

    public function forceDeleteAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ForceDeleteAny:AppSetting');
    }

    public function restoreAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('RestoreAny:AppSetting');
    }

    public function replicate(AuthUser $auth_user, AppSetting $app_setting): bool
    {
        unset($app_setting);

        return $auth_user->can('Replicate:AppSetting');
    }

    public function reorder(AuthUser $auth_user): bool
    {
        return $auth_user->can('Reorder:AppSetting');
    }
}
