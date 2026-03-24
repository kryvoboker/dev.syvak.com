<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes\Pages;

use App\Filament\Pages\Wiki\CatalogAttributesWikiPage;
use App\Filament\Resources\Catalogs\Attributes\Attributes\AttributeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListAttributes extends ListRecords
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_wiki')
                ->label(__('admin/wiki/wiki.actions.open_wiki'))
                ->icon(Heroicon::BookOpen)
                ->url(fn (): string => CatalogAttributesWikiPage::getUrl(), shouldOpenInNewTab: true),
            CreateAction::make(),
        ];
    }

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/catalogs/attributes/attributes.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/catalogs/attributes/attributes.navigation_label');
    }
}
