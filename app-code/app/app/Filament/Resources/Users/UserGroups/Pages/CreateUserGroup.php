<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups\Pages;

use App\Filament\Resources\Users\UserGroups\UserGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserGroup extends CreateRecord
{
    protected static string $resource = UserGroupResource::class;

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/users/user_groups.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/users/user_groups.navigation_label');
    }
}
