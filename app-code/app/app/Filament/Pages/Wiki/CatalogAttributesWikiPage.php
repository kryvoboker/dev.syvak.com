<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

use App\Filament\Navigation\AdminNavigationGroupEnum;

class CatalogAttributesWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/catalog-attributes';

    protected static ?int $navigationSort = 120;

    protected static function getWikiParentNavigationGroup(): AdminNavigationGroupEnum
    {
        return AdminNavigationGroupEnum::Catalog;
    }

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki.pages.catalog_attributes';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'catalog-attributes';
    }
}
