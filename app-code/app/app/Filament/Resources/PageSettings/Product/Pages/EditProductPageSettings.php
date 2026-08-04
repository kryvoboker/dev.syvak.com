<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Product\Pages;

use App\Filament\Resources\PageSettings\Product\ProductPageSettingResource;
use App\Models\PageSettings\PageSetting;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Throwable;

class EditProductPageSettings extends EditRecord
{
    protected static string $resource = ProductPageSettingResource::class;

    public function getTitle(): string
    {
        return __('admin/settings/product_page_settings.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/settings/product_page_settings.navigation_label');
    }

    /**
     * @return array|Action[]|ActionGroup[]
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
        ];
    }

    /**
     * @throws Throwable
     */
    public function mount(int|string|null $record = null): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapProductPageSetting();

        parent::mount($page_setting->id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PageSetting $record */
        $record = $this->getRecord();
        $settings = is_array($record->settings) ? $record->settings : [];

        $data['minimum_stock_quantity'] = max(
            0,
            (int) Arr::get(
                $settings,
                'customer.stock.minimum_stock_quantity',
                (int) Arr::get(
                    $settings,
                    'stock.minimum_stock_quantity',
                    (int) config('app.page_settings.product.for_customer.minimum_stock_quantity', (int) config('app.page_settings.product.minimum_stock_quantity', 1)),
                ),
            ),
        );
        $data['ean_max_length'] = max(
            1,
            (int) Arr::get(
                $settings,
                'admin.validation.ean_max_length',
                (int) Arr::get(
                    $settings,
                    'validation.ean_max_length',
                    (int) config('app.page_settings.product.for_admin.ean_max_length', (int) config('app.page_settings.product.ean_max_length', 13)),
                ),
            ),
        );
        $data['product_image_width'] = max(
            1,
            (int) Arr::get(
                $settings,
                'customer.images.product.width',
                (int) Arr::get(
                    $settings,
                    'images.product.width',
                    (int) config('app.page_settings.product.for_customer.image_width', (int) config('app.page_settings.product.image_width', 500)),
                ),
            ),
        );
        $data['product_image_height'] = max(
            1,
            (int) Arr::get(
                $settings,
                'customer.images.product.height',
                (int) Arr::get(
                    $settings,
                    'images.product.height',
                    (int) config('app.page_settings.product.for_customer.image_height', (int) config('app.page_settings.product.image_height', 500)),
                ),
            ),
        );
        $data['upload_max_size_mb'] = max(
            1,
            (int) ceil(
                (
                    (int) Arr::get(
                        $settings,
                        'admin.upload.max_size_kb',
                        (int) config('app.page_settings.product.for_admin.upload_max_size_kb', (int) config('app.images.product.upload.max_size_kb', 5120)),
                    )
                ) / 1024,
            ),
        );
        $data['image_upload_directory'] = (string) Arr::get(
            $settings,
            'admin.upload.directory',
            normalize_upload_path_template((string) config('app.page_settings.product.for_admin.image_upload_directory', (string) config('app.images.product.image_path', 'images/products/' . date('Y/m')))),
        );
        $data['no_image_path'] = (string) Arr::get(
            $settings,
            'admin.images.no_image.path',
            (string) config('app.page_settings.product.for_admin.no_image', (string) config('app.images.product.no_image', 'images/no-image.png')),
        );
        $data['preview_list_image_width'] = max(
            1,
            (int) Arr::get(
                $settings,
                'admin.images.preview_in_list.width',
                (int) config('app.page_settings.product.for_admin.preview_in_list_width', (int) config('app.images.product.preview_in_list_in_admin.width', 100)),
            ),
        );
        $data['preview_list_image_height'] = max(
            1,
            (int) Arr::get(
                $settings,
                'admin.images.preview_in_list.height',
                (int) config('app.page_settings.product.for_admin.preview_in_list_height', (int) config('app.images.product.preview_in_list_in_admin.height', 100)),
            ),
        );
        $data['preview_page_image_width'] = max(
            1,
            (int) Arr::get(
                $settings,
                'admin.images.preview_in_page.width',
                (int) config('app.page_settings.product.for_admin.preview_in_page_width', (int) config('app.images.product.preview_in_page_in_admin.width', 500)),
            ),
        );
        $data['preview_page_image_height'] = max(
            1,
            (int) Arr::get(
                $settings,
                'admin.images.preview_in_page.height',
                (int) config('app.page_settings.product.for_admin.preview_in_page_height', (int) config('app.images.product.preview_in_page_in_admin.height', 500)),
            ),
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
                'meta' => ['contract_version' => 2],
                'customer' => [
                    'stock' => [
                        'minimum_stock_quantity' => (int) config('app.page_settings.product.for_customer.minimum_stock_quantity', (int) config('app.page_settings.product.minimum_stock_quantity', 1)),
                    ],
                    'images' => [
                        'product' => [
                            'width' => (int) config('app.page_settings.product.for_customer.image_width', (int) config('app.page_settings.product.image_width', 500)),
                            'height' => (int) config('app.page_settings.product.for_customer.image_height', (int) config('app.page_settings.product.image_height', 500)),
                        ],
                    ],
                ],
                'admin' => [
                    'validation' => [
                        'ean_max_length' => (int) config('app.page_settings.product.for_admin.ean_max_length', (int) config('app.page_settings.product.ean_max_length', 13)),
                    ],
                    'upload' => [
                        'max_size_kb' => (int) config('app.page_settings.product.for_admin.upload_max_size_kb', (int) config('app.images.product.upload.max_size_kb', 5120)),
                        'directory' => normalize_upload_path_template((string) config('app.page_settings.product.for_admin.image_upload_directory', (string) config('app.images.product.image_path', 'images/products/' . date('Y/m')))),
                    ],
                    'images' => [
                        'no_image' => [
                            'path' => (string) config('app.page_settings.product.for_admin.no_image', (string) config('app.images.product.no_image', 'images/no-image.png')),
                        ],
                        'preview_in_list' => [
                            'width' => (int) config('app.page_settings.product.for_admin.preview_in_list_width', (int) config('app.images.product.preview_in_list_in_admin.width', 100)),
                            'height' => (int) config('app.page_settings.product.for_admin.preview_in_list_height', (int) config('app.images.product.preview_in_list_in_admin.height', 100)),
                        ],
                        'preview_in_page' => [
                            'width' => (int) config('app.page_settings.product.for_admin.preview_in_page_width', (int) config('app.images.product.preview_in_page_in_admin.width', 500)),
                            'height' => (int) config('app.page_settings.product.for_admin.preview_in_page_height', (int) config('app.images.product.preview_in_page_in_admin.height', 500)),
                        ],
                    ],
                ],
            ],
            $settings,
        );

        Arr::set($settings, 'meta.contract_version', 2);
        Arr::set($settings, 'customer.stock.minimum_stock_quantity', max(0, (int) Arr::get($data, 'minimum_stock_quantity', 1)));
        Arr::set($settings, 'customer.images.product.width', max(1, (int) Arr::get($data, 'product_image_width', 500)));
        Arr::set($settings, 'customer.images.product.height', max(1, (int) Arr::get($data, 'product_image_height', 500)));
        Arr::set($settings, 'admin.validation.ean_max_length', max(1, (int) Arr::get($data, 'ean_max_length', 13)));
        Arr::set($settings, 'admin.upload.max_size_kb', max(1, (int) Arr::get($data, 'upload_max_size_mb', 5) * 1024));
        Arr::set(
            $settings,
            'admin.upload.directory',
            normalize_upload_path_template((string) Arr::get($data, 'image_upload_directory', 'images/products/{year}/{month}')),
        );
        Arr::set($settings, 'admin.images.no_image.path', (string) Arr::get($data, 'no_image_path', 'images/no-image.png'));
        Arr::set($settings, 'admin.images.preview_in_list.width', max(1, (int) Arr::get($data, 'preview_list_image_width', 100)));
        Arr::set($settings, 'admin.images.preview_in_list.height', max(1, (int) Arr::get($data, 'preview_list_image_height', 100)));
        Arr::set($settings, 'admin.images.preview_in_page.width', max(1, (int) Arr::get($data, 'preview_page_image_width', 500)));
        Arr::set($settings, 'admin.images.preview_in_page.height', max(1, (int) Arr::get($data, 'preview_page_image_height', 500)));

        $record->update([
            'settings' => $settings,
        ]);

        return $record;
    }
}
