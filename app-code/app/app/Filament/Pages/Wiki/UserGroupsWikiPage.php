<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

use App\Filament\Navigation\AdminNavigationGroupEnum;

class UserGroupsWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/user-groups';

    protected static ?int $navigationSort = 310;

    protected static function getWikiParentNavigationGroup(): AdminNavigationGroupEnum
    {
        return AdminNavigationGroupEnum::Users;
    }

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki/wiki.pages.user_groups';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'user-groups';
    }
}
