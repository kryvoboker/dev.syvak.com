<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups\Pages;

use App\Filament\Pages\Wiki\UserGroupsWikiPage;
use App\Filament\Resources\Users\UserGroups\UserGroupResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUserGroups extends ListRecords
{
    protected static string $resource = UserGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_wiki')
                ->label(__('admin/wiki.actions.open_wiki'))
                ->icon(Heroicon::BookOpen)
                ->url(fn (): string => UserGroupsWikiPage::getUrl(), shouldOpenInNewTab: true),
            CreateAction::make(),
        ];
    }

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/users/user_groups.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/users/user_groups.navigation_label');
    }
}
