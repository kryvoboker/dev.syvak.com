<?php

declare(strict_types=1);

namespace App\Policies\Settings;

use App\Models\ApplicationSettings\Language;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LanguagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ViewAny:Language');
    }

    public function view(AuthUser $auth_user, Language $language): bool
    {
        unset($language);

        return $auth_user->can('View:Language');
    }

    public function create(AuthUser $auth_user): bool
    {
        return $auth_user->can('Create:Language');
    }

    public function update(AuthUser $auth_user, Language $language): bool
    {
        unset($language);

        return $auth_user->can('Update:Language');
    }

    public function delete(AuthUser $auth_user, Language $language): bool
    {
        unset($language);

        return $auth_user->can('Delete:Language');
    }

    public function restore(AuthUser $auth_user, Language $language): bool
    {
        unset($language);

        return $auth_user->can('Restore:Language');
    }

    public function forceDelete(AuthUser $auth_user, Language $language): bool
    {
        unset($language);

        return $auth_user->can('ForceDelete:Language');
    }

    public function forceDeleteAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ForceDeleteAny:Language');
    }

    public function restoreAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('RestoreAny:Language');
    }

    public function replicate(AuthUser $auth_user, Language $language): bool
    {
        unset($language);

        return $auth_user->can('Replicate:Language');
    }

    public function reorder(AuthUser $auth_user): bool
    {
        return $auth_user->can('Reorder:Language');
    }
}
