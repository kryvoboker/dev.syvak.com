<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Users\Pages;

use App\Filament\Pages\Wiki\UsersWikiPage;
use App\Filament\Resources\Users\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_wiki')
                ->label(__('admin/wiki/wiki.actions.open_wiki'))
                ->icon(Heroicon::BookOpen)
                ->url(fn (): string => UsersWikiPage::getUrl(), shouldOpenInNewTab: true),
            CreateAction::make(),
        ];
    }

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/users/users.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/users/users.navigation_label');
    }
}
