<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

use App\Filament\Navigation\AdminNavigationGroupEnum;

class ModulesWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/modules';

    protected static ?int $navigationSort = 400;

    protected static function getWikiParentNavigationGroup(): AdminNavigationGroupEnum
    {
        return AdminNavigationGroupEnum::Modules;
    }

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki/wiki.pages.modules';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'modules';
    }
}
