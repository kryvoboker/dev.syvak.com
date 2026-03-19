<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

class UserGroupsWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/user-groups';

    protected static ?int $navigationSort = 310;

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki.pages.user_groups';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'user-groups';
    }
}
