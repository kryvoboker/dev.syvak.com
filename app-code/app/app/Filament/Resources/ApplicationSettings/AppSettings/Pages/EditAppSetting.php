<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\AppSettings\Pages;

use App\Filament\Pages\Wiki\ApplicationSettingsWikiPage;
use App\Filament\Resources\ApplicationSettings\AppSettings\AppSettingResource;
use App\Models\ApplicationSettings\AppSetting;
use App\Supports\Services\AppSettingsService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

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
            'admin/settings' => __('admin/default.menu.item_application_settings'),
            AppSettingResource::getUrl('index') => __('admin/settings/app_settings.navigation_label'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_wiki')
                ->label(__('admin/wiki/wiki.actions.open_wiki'))
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
                'titles' => [],
                'meta_titles' => [],
                'meta_descriptions' => [],
                'meta_keywords' => [],
                'socials' => [],
                'timezone' => config('app.timezone'),
                'image_sizes' => [],
                'system_settings' => [],
                'user_settings' => [],
                'ai_settings' => [],
            ],
        );

        parent::mount($settings_record->id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var AppSetting $record */
        $record = $this->getRecord();
        $data['system_settings'] = $this->normalizeSystemSettingsForForm($record);
        $data['user_settings'] = $this->normalizeUserSettingsForForm($record);
        $data['ai_settings'] = $this->normalizeAiSettingsForForm($record);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var AppSetting $record */
        $ai_settings = $this->normalizeAiSettingsForSave((array) Arr::get($data, 'ai_settings', []));
        $system_settings = $this->normalizeSystemSettingsForSave((array) Arr::get($data, 'system_settings', []));
        $user_settings = $this->normalizeUserSettingsForSave((array) Arr::get($data, 'user_settings', []));

        $record->update([
            'titles' => Arr::get($data, 'titles', []),
            'meta_titles' => Arr::get($data, 'meta_titles', []),
            'meta_descriptions' => Arr::get($data, 'meta_descriptions', []),
            'meta_keywords' => Arr::get($data, 'meta_keywords', []),
            'socials' => Arr::get($data, 'socials', []),
            'timezone' => Arr::get($data, 'timezone', config('app.timezone')),
            'image_sizes' => $this->normalizeSystemImageSizesForSave((array) Arr::get($data, 'image_sizes', [])),
            'system_settings' => $system_settings,
            'user_settings' => $user_settings,
            'ai_settings' => $ai_settings,
        ]);

        app(AppSettingsService::class)->removeSettings();

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeUserSettingsForForm(AppSetting $record): array
    {
        $user_settings = is_array($record->user_settings) ? $record->user_settings : [];

        return [
            'upload' => [
                'max_size_mb' => max(
                    1,
                    (int) ceil(
                        (
                            (int) Arr::get($user_settings, 'upload.max_size_kb', (int) config('app.images.user.upload.max_size_kb', 5120))
                        ) / 1024,
                    ),
                ),
            ],
            'image_path' => normalize_upload_path_template((string) Arr::get($user_settings, 'image_path', (string) config('app.images.user.image_path', 'images/avatars/' . date('Y/m')))),
            'no_image' => (string) Arr::get($user_settings, 'no_image', (string) config('app.images.user.no_image', 'images/no-avatar.png')),
            'preview_in_list_in_admin' => [
                'width' => max(
                    1,
                    (int) Arr::get($user_settings, 'preview_in_list_in_admin.width', (int) config('app.images.user.preview_in_list_in_admin.width', 100)),
                ),
                'height' => max(
                    1,
                    (int) Arr::get($user_settings, 'preview_in_list_in_admin.height', (int) config('app.images.user.preview_in_list_in_admin.height', 100)),
                ),
            ],
            'preview_in_page_in_admin' => [
                'width' => max(
                    1,
                    (int) Arr::get($user_settings, 'preview_in_page_in_admin.width', (int) config('app.images.user.preview_in_page_in_admin.width', 500)),
                ),
                'height' => max(
                    1,
                    (int) Arr::get($user_settings, 'preview_in_page_in_admin.height', (int) config('app.images.user.preview_in_page_in_admin.height', 500)),
                ),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $user_settings
     * @return array<string, mixed>
     */
    private function normalizeUserSettingsForSave(array $user_settings): array
    {
        return [
            'upload' => [
                'max_size_kb' => max(
                    1,
                    (int) Arr::get($user_settings, 'upload.max_size_mb', 5) * 1024,
                ),
            ],
            'image_path' => normalize_upload_path_template((string) Arr::get($user_settings, 'image_path', (string) config('app.images.user.image_path', 'images/avatars/' . date('Y/m')))),
            'no_image' => (string) Arr::get($user_settings, 'no_image', (string) config('app.images.user.no_image', 'images/no-avatar.png')),
            'preview_in_list_in_admin' => [
                'width' => max(
                    1,
                    (int) Arr::get($user_settings, 'preview_in_list_in_admin.width', (int) config('app.images.user.preview_in_list_in_admin.width', 100)),
                ),
                'height' => max(
                    1,
                    (int) Arr::get($user_settings, 'preview_in_list_in_admin.height', (int) config('app.images.user.preview_in_list_in_admin.height', 100)),
                ),
            ],
            'preview_in_page_in_admin' => [
                'width' => max(
                    1,
                    (int) Arr::get($user_settings, 'preview_in_page_in_admin.width', (int) config('app.images.user.preview_in_page_in_admin.width', 500)),
                ),
                'height' => max(
                    1,
                    (int) Arr::get($user_settings, 'preview_in_page_in_admin.height', (int) config('app.images.user.preview_in_page_in_admin.height', 500)),
                ),
            ],
        ];
    }

    /**
     * Get redirect URL after save
     */
    protected function getRedirectUrl(): ?string
    {
        return null; // Stay on same page after save
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAiSettingsForForm(AppSetting $record): array
    {
        $ai_settings = is_array($record->ai_settings) ? $record->ai_settings : [];

        return [
            'api_model' => (string) Arr::get($ai_settings, 'api_model', (string) config('open-ai.api_model', 'gpt-5-mini')),
            'api_temperature' => (float) Arr::get($ai_settings, 'api_temperature', (float) config('open-ai.api_temperature', 0.5)),
            'api_max_tokens' => max(1, (int) Arr::get($ai_settings, 'api_max_tokens', (int) config('open-ai.api_max_tokens', 2048))),
            'system_prompt' => (string) Arr::get($ai_settings, 'system_prompt', (string) config('open-ai.system_prompt', '')),
            'api_wait_time_seconds' => max(0, (int) Arr::get($ai_settings, 'api_wait_time_seconds', (int) config('open-ai.api_wait_time_seconds', 1))),
            'api_max_calls' => max(1, (int) Arr::get($ai_settings, 'api_max_calls', (int) config('open-ai.api_max_calls', 3))),
            'api_max_retries' => max(0, (int) Arr::get($ai_settings, 'api_max_retries', (int) config('open-ai.api_max_retries', 3))),
            'api_max_retry_wait_time_seconds' => max(0, (int) Arr::get($ai_settings, 'api_max_retry_wait_time_seconds', (int) config('open-ai.api_max_retry_wait_time_seconds', 1))),
        ];
    }

    /**
     * @param  array<string, mixed>  $ai_settings
     * @return array<string, mixed>
     */
    private function normalizeAiSettingsForSave(array $ai_settings): array
    {
        return [
            'api_model' => (string) Arr::get($ai_settings, 'api_model', (string) config('open-ai.api_model', 'gpt-5-mini')),
            'api_temperature' => (float) Arr::get($ai_settings, 'api_temperature', (float) config('open-ai.api_temperature', 0.5)),
            'api_max_tokens' => max(1, (int) Arr::get($ai_settings, 'api_max_tokens', (int) config('open-ai.api_max_tokens', 2048))),
            'system_prompt' => (string) Arr::get($ai_settings, 'system_prompt', (string) config('open-ai.system_prompt', '')),
            'api_wait_time_seconds' => max(0, (int) Arr::get($ai_settings, 'api_wait_time_seconds', (int) config('open-ai.api_wait_time_seconds', 1))),
            'api_max_calls' => max(1, (int) Arr::get($ai_settings, 'api_max_calls', (int) config('open-ai.api_max_calls', 3))),
            'api_max_retries' => max(0, (int) Arr::get($ai_settings, 'api_max_retries', (int) config('open-ai.api_max_retries', 3))),
            'api_max_retry_wait_time_seconds' => max(0, (int) Arr::get($ai_settings, 'api_max_retry_wait_time_seconds', (int) config('open-ai.api_max_retry_wait_time_seconds', 1))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSystemSettingsForForm(AppSetting $record): array
    {
        $system_settings = is_array($record->system_settings) ? $record->system_settings : [];

        return [
            'max_viewport_width' => max(
                1,
                (int) Arr::get($system_settings, 'frontend.max_viewport_width', (int) config('app.frontend.max_viewport_width', 1920)),
            ),
            'images' => [
                'path_to_logo' => (string) Arr::get($system_settings, 'images.path_to_logo', (string) config('app.images.path_to_logo', 'images/logo.png')),
                'default_no_image' => (string) Arr::get($system_settings, 'images.default_no_image', (string) config('app.images.default_no_image', 'images/no-image.png')),
                'prototype_quality' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.prototype_quality', (int) config('app.images.prototype_quality', 100)),
                ),
                'webp_quality' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.webp_quality', (int) config('app.images.webp_quality', 80)),
                ),
                'avif_quality' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.avif_quality', (int) config('app.images.avif_quality', 50)),
                ),
                'total_sizes_for_generate' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.total_sizes_for_generate', (int) config('app.images.total_sizes_for_generate', 4)),
                ),
                'max_image_width_for_convert' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.max_image_width_for_convert', (int) config('app.images.max_image_width_for_convert', 2500)),
                ),
                'max_image_height_for_convert' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.max_image_height_for_convert', (int) config('app.images.max_image_height_for_convert', 2500)),
                ),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $system_settings
     * @return array<string, mixed>
     */
    private function normalizeSystemSettingsForSave(array $system_settings): array
    {
        return [
            'frontend' => [
                'max_viewport_width' => max(
                    1,
                    (int) Arr::get($system_settings, 'max_viewport_width', (int) config('app.frontend.max_viewport_width', 1920)),
                ),
            ],
            'images' => [
                'path_to_logo' => (string) Arr::get($system_settings, 'images.path_to_logo', (string) config('app.images.path_to_logo', 'images/logo.png')),
                'default_no_image' => (string) Arr::get($system_settings, 'images.default_no_image', (string) config('app.images.default_no_image', 'images/no-image.png')),
                'prototype_quality' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.prototype_quality', (int) config('app.images.prototype_quality', 100)),
                ),
                'webp_quality' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.webp_quality', (int) config('app.images.webp_quality', 80)),
                ),
                'avif_quality' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.avif_quality', (int) config('app.images.avif_quality', 50)),
                ),
                'total_sizes_for_generate' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.total_sizes_for_generate', (int) config('app.images.total_sizes_for_generate', 4)),
                ),
                'max_image_width_for_convert' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.max_image_width_for_convert', (int) config('app.images.max_image_width_for_convert', 2500)),
                ),
                'max_image_height_for_convert' => max(
                    1,
                    (int) Arr::get($system_settings, 'images.max_image_height_for_convert', (int) config('app.images.max_image_height_for_convert', 2500)),
                ),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $image_sizes
     * @return array<int, array{name:string,width:int,height:int}>
     */
    private function normalizeSystemImageSizesForSave(array $image_sizes): array
    {
        return collect($image_sizes)
            ->filter(function (mixed $item): bool {
                $name = (string) Arr::get((array) $item, 'name');

                return ! in_array($name, ['logo', 'search_product', 'search_not_found'], true);
            })
            ->map(function (mixed $item): array {
                return [
                    'name' => (string) Arr::get((array) $item, 'name'),
                    'width' => max(1, (int) Arr::get((array) $item, 'width', 1)),
                    'height' => max(1, (int) Arr::get((array) $item, 'height', 1)),
                ];
            })
            ->values()
            ->all();
    }
}
