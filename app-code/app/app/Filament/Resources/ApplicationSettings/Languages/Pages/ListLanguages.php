<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\Languages\Pages;

use App\Filament\Resources\ApplicationSettings\Languages\LanguageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLanguages extends ListRecords
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

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
