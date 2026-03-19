<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

class UsersWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/users';

    protected static ?int $navigationSort = 300;

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki.pages.users';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'users';
    }
}
