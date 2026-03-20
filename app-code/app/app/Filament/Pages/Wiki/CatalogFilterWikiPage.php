<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

use App\Filament\Navigation\AdminNavigationGroupEnum;

class CatalogFilterWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/catalog-filter';

    protected static ?int $navigationSort = 130;

    protected static function getWikiParentNavigationGroup(): AdminNavigationGroupEnum
    {
        return AdminNavigationGroupEnum::Catalog;
    }

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki_catalog_filter';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'catalog-filter';
    }
}
