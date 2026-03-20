<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

use App\Filament\Navigation\AdminNavigationGroupEnum;

class ApplicationCurrenciesWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/application-currencies';

    protected static ?int $navigationSort = 510;

    protected static function getWikiParentNavigationGroup(): AdminNavigationGroupEnum
    {
        return AdminNavigationGroupEnum::ApplicationSettings;
    }

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki.pages.application_currencies';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'application-currencies';
    }
}
