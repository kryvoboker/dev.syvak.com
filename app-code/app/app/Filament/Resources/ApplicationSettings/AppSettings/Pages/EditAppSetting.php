<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\AppSettings\Pages;

use App\Filament\Pages\Wiki\ApplicationSettingsWikiPage;
use App\Filament\Resources\ApplicationSettings\AppSettings\AppSettingResource;
use App\Models\ApplicationSettings\AppSetting;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAppSetting extends EditRecord
{
    protected static string $resource = AppSettingResource::class;

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/settings/app_settings.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/settings/app_settings.navigation_label');
    }

    /**
     * Get breadcrumbs
     */
    public function getBreadcrumbs(): array
    {
        return [
            'admin/settings'                    => __('admin/default.menu.item_settings'),
            AppSettingResource::getUrl('index') => __('admin/settings/app_settings.navigation_label'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_wiki')
                ->label(__('admin/wiki.actions.open_wiki'))
                ->icon(Heroicon::BookOpen)
                ->url(fn (): string => ApplicationSettingsWikiPage::getUrl(), shouldOpenInNewTab: true),
        ];
    }

    /**
     * Mount the page and get or create settings record
     */
    public function mount(int|string|null $record = null): void
    {
        // Get first settings record or create if not exists
        /** @var AppSetting $settings_record */
        $settings_record = AppSetting::firstOrCreate(
            [],
            [
                'titles'            => [],
                'meta_titles'       => [],
                'meta_descriptions' => [],
                'meta_keywords'     => [],
                'contact_emails'    => [],
                'contact_phones'    => [],
                'socials'           => [],
                'work_time'         => [],
                'contact_addresses' => [],
                'coordinates'       => null,
                'iframe_map'        => null,
                'timezone'          => config('app.timezone'),
                'image_sizes'       => [],
            ],
        );

        parent::mount($settings_record->id);
    }

    /**
     * Get redirect URL after save
     */
    protected function getRedirectUrl(): ?string
    {
        return null; // Stay on same page after save
    }
}
