<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

use App\Filament\Navigation\AdminNavigationGroupEnum;

class CategoryPageSettingsWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/page-settings-categories';

    protected static ?int $navigationSort = 500;

    protected static function getWikiParentNavigationGroup(): AdminNavigationGroupEnum
    {
        return AdminNavigationGroupEnum::PageSettings;
    }

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki/wiki.pages.page_settings_category';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'category-page-settings';
    }
}
