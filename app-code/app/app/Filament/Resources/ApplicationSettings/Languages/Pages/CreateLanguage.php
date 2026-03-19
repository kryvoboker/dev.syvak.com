<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\Languages\Pages;

use App\Filament\Resources\ApplicationSettings\Languages\LanguageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLanguage extends CreateRecord
{
    protected static string $resource = LanguageResource::class;

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/settings/languages.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/settings/languages.navigation_label');
    }
}
