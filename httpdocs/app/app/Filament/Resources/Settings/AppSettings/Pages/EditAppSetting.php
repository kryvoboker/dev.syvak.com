<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\AppSettings\Pages;

use App\Filament\Resources\Settings\AppSettings\AppSettingResource;
use App\Models\Settings\AppSetting;
use Filament\Resources\Pages\EditRecord;

class EditAppSetting extends EditRecord
{
    protected static string $resource = AppSettingResource::class;

    /**
     * Get page title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return __('admin/settings/app_settings.navigation_label');
    }

    /**
     * Get page heading
     *
     * @return string|null
     */
    public function getHeading(): ?string
    {
        return __('admin/settings/app_settings.navigation_label');
    }

    /**
     * Get breadcrumbs
     *
     * @return array
     */
    public function getBreadcrumbs(): array
    {
        return [
            'admin/settings'                    => __('admin/default.item_settings'),
            AppSettingResource::getUrl('index') => __('admin/settings/app_settings.navigation_label'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Mount the page and get or create settings record
     *
     * @param int|string|null $record
     *
     * @return void
     */
    public function mount(int|string|null $record = null): void
    {
        // Get first settings record or create if not exists
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
            ]
        );

        parent::mount($settings_record->id);
    }

    /**
     * Get redirect URL after save
     *
     * @return string|null
     */
    protected function getRedirectUrl(): ?string
    {
        return null; // Stay on same page after save
    }
}
