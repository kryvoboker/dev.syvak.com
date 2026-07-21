<?php

declare(strict_types=1);

namespace App\Policies\Orders;

use App\Models\Orders\Orders;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrdersPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ViewAny:Orders');
    }

    public function view(AuthUser $auth_user, Orders $order): bool
    {
        unset($order);

        return $auth_user->can('View:Orders');
    }

    public function update(AuthUser $auth_user, Orders $order): bool
    {
        unset($order);

        return $auth_user->can('Update:Orders');
    }

    public function delete(AuthUser $auth_user, Orders $order): bool
    {
        unset($order);

        return $auth_user->can('Delete:Orders');
    }

    public function deleteAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('DeleteAny:Orders');
    }

    public function restore(AuthUser $auth_user, Orders $order): bool
    {
        unset($order);

        return $auth_user->can('Restore:Orders');
    }

    public function forceDelete(AuthUser $auth_user, Orders $order): bool
    {
        unset($order);

        return $auth_user->can('ForceDelete:Orders');
    }

    public function forceDeleteAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('ForceDeleteAny:Orders');
    }

    public function restoreAny(AuthUser $auth_user): bool
    {
        return $auth_user->can('RestoreAny:Orders');
    }

    public function replicate(AuthUser $auth_user, Orders $order): bool
    {
        unset($order);

        return $auth_user->can('Replicate:Orders');
    }

    public function reorder(AuthUser $auth_user): bool
    {
        return $auth_user->can('Reorder:Orders');
    }
}
