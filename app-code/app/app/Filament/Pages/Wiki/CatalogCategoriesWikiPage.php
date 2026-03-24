<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

use App\Filament\Navigation\AdminNavigationGroupEnum;

class CatalogCategoriesWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/catalog-categories';

    protected static ?int $navigationSort = 110;

    protected static function getWikiParentNavigationGroup(): AdminNavigationGroupEnum
    {
        return AdminNavigationGroupEnum::Catalog;
    }

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki/wiki.pages.catalog_categories';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'catalog-categories';
    }
}
