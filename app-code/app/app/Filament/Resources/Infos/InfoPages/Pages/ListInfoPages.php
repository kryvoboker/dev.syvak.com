<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Pages;

use App\Filament\Pages\Wiki\InfoPagesWikiPage;
use App\Filament\Resources\Infos\InfoPages\InfoPageResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListInfoPages extends ListRecords
{
    protected static string $resource = InfoPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_wiki')
                ->label(__('admin/wiki/wiki.actions.open_wiki'))
                ->icon(Heroicon::BookOpen)
                ->url(fn (): string => InfoPagesWikiPage::getUrl(), shouldOpenInNewTab: true),
            CreateAction::make(),
        ];
    }
}
