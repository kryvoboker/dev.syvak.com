<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

use App\Filament\Navigation\AdminNavigationGroupEnum;

class InfoPagesWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/info-pages';

    protected static ?int $navigationSort = 200;

    protected static function getWikiParentNavigationGroup(): AdminNavigationGroupEnum
    {
        return AdminNavigationGroupEnum::InfoPages;
    }

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki/wiki.pages.info_pages';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'info-pages';
    }
}
