<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Pages;

use App\Filament\Pages\Wiki\CatalogCategoriesWikiPage;
use App\Filament\Resources\Catalogs\Categories\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_wiki')
                ->label(__('admin/wiki.actions.open_wiki'))
                ->icon(Heroicon::BookOpen)
                ->url(fn (): string => CatalogCategoriesWikiPage::getUrl(), shouldOpenInNewTab: true),
            CreateAction::make(),
        ];
    }

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/catalogs/categories/categories.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/catalogs/categories/categories.navigation_label');
    }
}
