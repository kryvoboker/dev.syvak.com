<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

class ApplicationLanguagesWikiPage extends BaseWikiPage
{
    protected static ?string $slug = 'wiki/application-languages';

    protected static ?int $navigationSort = 520;

    protected static function getWikiTranslationPath(): string
    {
        return 'admin/wiki.pages.application_languages';
    }

    protected static function getWikiScreenshotDirectory(): string
    {
        return 'application-languages';
    }
}
