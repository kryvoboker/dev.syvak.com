<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

class InfoPagesWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/info-pages';

    protected static ?int $navigationSort = 200;

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki.pages.info_pages';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'info-pages';
    }
}
