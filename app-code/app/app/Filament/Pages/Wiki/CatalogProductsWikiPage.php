<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

use App\Filament\Navigation\AdminNavigationGroupEnum;

class CatalogProductsWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/catalog-products';

    protected static ?int $navigationSort = 100;

    protected static function getWikiParentNavigationGroup(): AdminNavigationGroupEnum
    {
        return AdminNavigationGroupEnum::Catalog;
    }

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki/wiki.pages.catalog_products';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'catalog-products';
    }
}
