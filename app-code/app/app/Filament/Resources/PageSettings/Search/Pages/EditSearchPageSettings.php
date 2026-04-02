<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Search\Pages;

use App\Filament\Resources\PageSettings\Search\SearchPageSettingResource;
use App\Models\PageSettings\PageSetting;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Throwable;

class EditSearchPageSettings extends EditRecord
{
    protected static string $resource = SearchPageSettingResource::class;

    public function getTitle(): string
    {
        return __('admin/settings/search_page_settings.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/settings/search_page_settings.navigation_label');
    }

    /**
     * @throws Throwable
     */
    public function mount(int|string|null $record = null): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapSearchPageSetting();

        parent::mount($page_setting->id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PageSetting $record */
        $record   = $this->getRecord();
        $settings = is_array($record->settings) ? $record->settings : [];

        $data['products_per_page_limit'] = max(
            1,
            (int) Arr::get($settings, 'pagination.products_per_page_limit', (int) config('app.page_settings.search.products_per_page_limit', 15)),
        );
        $data['search_product_image_width'] = max(
            1,
            (int) Arr::get($settings, 'images.search_product.width', (int) config('app.page_settings.search.images.search_product.width', 219)),
        );
        $data['search_product_image_height'] = max(
            1,
            (int) Arr::get($settings, 'images.search_product.height', (int) config('app.page_settings.search.images.search_product.height', 219)),
        );
        $data['search_not_found_image_path'] = (string) Arr::get(
            $settings,
            'images.search_not_found.path',
            (string) Arr::get(config('app.page_settings.search', []), 'images.search_not_found.path', 'images/search/not-found.jpg'),
        );
        $data['search_not_found_image_width'] = max(
            1,
            (int) Arr::get($settings, 'images.search_not_found.width', (int) config('app.page_settings.search.images.search_not_found.width', 600)),
        );
        $data['search_not_found_image_height'] = max(
            1,
            (int) Arr::get($settings, 'images.search_not_found.height', (int) config('app.page_settings.search.images.search_not_found.height', 600)),
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PageSetting $record */
        $settings = is_array($record->settings) ? $record->settings : [];

        $settings = array_replace_recursive(
            [
                'meta'       => ['contract_version' => 2],
                'pagination' => [
                    'products_per_page_limit' => (int) config('app.page_settings.search.products_per_page_limit', 15),
                ],
                'images' => [
                    'search_product' => [
                        'width'  => (int) config('app.page_settings.search.images.search_product.width', 219),
                        'height' => (int) config('app.page_settings.search.images.search_product.height', 219),
                    ],
                    'search_not_found' => [
                        'path'   => (string) Arr::get(config('app.page_settings.search', []), 'images.search_not_found.path', 'images/search/not-found.jpg'),
                        'width'  => (int) config('app.page_settings.search.images.search_not_found.width', 600),
                        'height' => (int) config('app.page_settings.search.images.search_not_found.height', 600),
                    ],
                ],
            ],
            $settings,
        );

        Arr::set($settings, 'meta.contract_version', 2);
        Arr::set($settings, 'pagination.products_per_page_limit', max(1, (int) Arr::get($data, 'products_per_page_limit', 15)));
        Arr::set($settings, 'images.search_product.width', max(1, (int) Arr::get($data, 'search_product_image_width', 219)));
        Arr::set($settings, 'images.search_product.height', max(1, (int) Arr::get($data, 'search_product_image_height', 219)));
        Arr::set($settings, 'images.search_not_found.path', (string) Arr::get($data, 'search_not_found_image_path', 'images/search/not-found.jpg'));
        Arr::set($settings, 'images.search_not_found.width', max(1, (int) Arr::get($data, 'search_not_found_image_width', 600)));
        Arr::set($settings, 'images.search_not_found.height', max(1, (int) Arr::get($data, 'search_not_found_image_height', 600)));

        $record->update([
            'settings' => $settings,
        ]);

        return $record;
    }
}
