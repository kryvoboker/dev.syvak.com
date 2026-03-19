<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

class ModulesWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/modules';

    protected static ?int $navigationSort = 400;

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki.pages.modules';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'modules';
    }
}
