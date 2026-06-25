<?php

declare(strict_types=1);

namespace App\Policies\Settings;

use App\Models\ApplicationSettings\Currency;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CurrencyPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ViewAny:Currency');
    }

    public function view(AuthUser $auth_user, Currency $currency): bool
    {
        unset($currency);

        return $auth_user->can('View:Currency');
    }

    public function create(AuthUser $auth_user): bool
    {
        return $auth_user->can('Create:Currency');
    }

    public function update(AuthUser $auth_user, Currency $currency): bool
    {
        unset($currency);

        return $auth_user->can('Update:Currency');
    }

    public function delete(AuthUser $auth_user, Currency $currency): bool
    {
        unset($currency);

        return $auth_user->can('Delete:Currency');
    }

    public function restore(AuthUser $auth_user, Currency $currency): bool
    {
        unset($currency);

        return $auth_user->can('Restore:Currency');
    }

    public function forceDelete(AuthUser $auth_user, Currency $currency): bool
    {
        unset($currency);

        return $auth_user->can('ForceDelete:Currency');
    }

    public function forceDeleteAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ForceDeleteAny:Currency');
    }

    public function restoreAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('RestoreAny:Currency');
    }

    public function replicate(AuthUser $auth_user, Currency $currency): bool
    {
        unset($currency);

        return $auth_user->can('Replicate:Currency');
    }

    public function reorder(AuthUser $auth_user): bool
    {
        return $auth_user->can('Reorder:Currency');
    }
}
