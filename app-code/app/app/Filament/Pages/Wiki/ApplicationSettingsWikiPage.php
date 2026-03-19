<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

class ApplicationSettingsWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/application-settings';

    protected static ?int $navigationSort = 530;

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki.pages.application_settings';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'application-settings';
    }
}
