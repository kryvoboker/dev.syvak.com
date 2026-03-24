<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\Languages\Pages;

use App\Filament\Pages\Wiki\ApplicationLanguagesWikiPage;
use App\Filament\Resources\ApplicationSettings\Languages\LanguageResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

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
            Action::make('open_wiki')
                ->label(__('admin/wiki/wiki.actions.open_wiki'))
                ->icon(Heroicon::BookOpen)
                ->url(fn (): string => ApplicationLanguagesWikiPage::getUrl(), shouldOpenInNewTab: true),
            CreateAction::make(),
        ];
    }
}
