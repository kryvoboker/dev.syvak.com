<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Attributes\Attribute;
use App\Models\PageSettings\PageSetting;
use App\Models\PageSettings\PageSettingItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CategoryPageFilterSyncService
{
    /**
     * @throws Throwable
     *
     * @return array<string, int>
     */
    public function sync(PageSetting $page_setting): array
    {
        try {
            $default_language_id  = $this->resolveDefaultLanguageId();
            $filter_item_payloads = $this->buildFilterItemPayloads($default_language_id);

            $summary = $this->syncFilterItems($page_setting, $filter_item_payloads);

            Log::channel('daily')->info(
                'Category page filters synchronized.',
                [
                    'page_setting_id'       => (int) $page_setting->id,
                    'default_language_id'   => $default_language_id,
                    'created_count'         => $summary['created_count'],
                    'updated_count'         => $summary['updated_count'],
                    'removed_count'         => $summary['removed_count'],
                    'source_payloads_count' => count($filter_item_payloads),
                ],
            );

            return $summary;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error(
                'Category page filters synchronization failed.',
                [
                    'page_setting_id' => (int) $page_setting->id,
                    'exception'       => $throwable,
                ],
            );

            throw $throwable;
        }
    }

    private function resolveDefaultLanguageId(): int
    {
        $default_language = new Language()->getDefaultLanguage();

        if ($default_language !== null) {
            return (int) $default_language->id;
        }

        $first_active_language = new Language()->getActiveLanguages()->first();

        if ($first_active_language instanceof Language) {
            return (int) $first_active_language->id;
        }

        return 1;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFilterItemPayloads(int $language_id): array
    {
        $payloads = [
            $this->buildPriceFilterPayload(),
            $this->buildStockFilterPayload(),
            ...$this->buildAttributeFilterPayloads($language_id),
        ];

        foreach ($payloads as $index => &$payload) {
            $payload['sort_order'] = (int) (($index + 1) * 10);
        }
        unset($payload);

        return $payloads;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPriceFilterPayload(): array
    {
        $now = now(config('app.timezone'));

        $base_price_stats = DB::table('products')
            ->where('is_active', true)
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        $discount_price_stats = DB::table('product_discounts')
            ->where('date_start', '<=', $now)
            ->where('date_end', '>=', $now)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('products')
                    ->whereColumn('products.id', 'product_discounts.product_id')
                    ->where('products.is_active', true);
            })
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        $min_price = collect([
            $base_price_stats?->min_price,
            $discount_price_stats?->min_price,
        ])
            ->filter(fn (mixed $price): bool => is_numeric($price))
            ->map(fn (mixed $price): float => (float) $price)
            ->min();

        $max_price = collect([
            $base_price_stats?->max_price,
            $discount_price_stats?->max_price,
        ])
            ->filter(fn (mixed $price): bool => is_numeric($price))
            ->map(fn (mixed $price): float => (float) $price)
            ->max();

        return [
            'code'        => CatalogFilterGroupSourceTypeEnum::Price->value,
            'source_type' => CatalogFilterGroupSourceTypeEnum::Price->value,
            'source_id'   => null,
            'is_enabled'  => true,
            'get'         => [
                'key'   => CatalogFilterGroupSourceTypeEnum::Price->value,
                'value' => null,
                'extra' => [
                    'from_key' => 'price_from',
                    'to_key'   => 'price_to',
                ],
            ],
            'config' => [
                'mode'      => 'range',
                'min_price' => $min_price,
                'max_price' => $max_price,
                'step'      => 1,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStockFilterPayload(): array
    {
        return [
            'code'        => 'stock',
            'source_type' => 'stock',
            'source_id'   => null,
            'is_enabled'  => true,
            'get'         => [
                'key'   => 'stock',
                'value' => 'available',
                'extra' => [],
            ],
            'config' => [
                'mode' => 'boolean',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAttributeFilterPayloads(int $language_id): array
    {
        $attributes = Attribute::query()
            ->where('is_active', true)
            ->whereHas('productToAttribute.product', function ($query): void {
                $query->where('is_active', true);
            })
            ->with([
                'attributeDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $attributes->map(function (Attribute $attribute): array {
            $attribute_discription = $attribute->attributeDescription->first();
            $attribute_name        = (string) $attribute_discription?->name;

            return [
                'code'        => CatalogFilterGroupSourceTypeEnum::Attribute->value . '_' . (int) $attribute->id,
                'source_type' => CatalogFilterGroupSourceTypeEnum::Attribute->value,
                'source_id'   => (int) $attribute->id,
                'is_enabled'  => true,
                'get'         => [
                    'key'   => CatalogFilterGroupSourceTypeEnum::Attribute->value . 's[' . (int) $attribute->id . ']',
                    'value' => null,
                    'extra' => [
                        'mode' => 'multiple',
                    ],
                ],
                'config' => [
                    'label' => $attribute_name,
                    'mode'  => 'multiple',
                ],
            ];
        })->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $payloads
     * @return array<string, int>
     */
    private function syncFilterItems(PageSetting $page_setting, array $payloads): array
    {
        $created_count = 0;
        $updated_count = 0;

        $payload_codes = collect($payloads)
            ->pluck('code')
            ->filter(fn (mixed $code): bool => is_string($code) && filled($code))
            ->values()
            ->all();

        $removed_count = PageSettingItem::query()
            ->where('page_setting_id', (int) $page_setting->id)
            ->where('type', PageSetting::ITEM_TYPE_FILTER)
            ->whereNotIn('code', $payload_codes)
            ->delete();

        foreach ($payloads as $payload) {
            $item = PageSettingItem::query()->firstOrNew([
                'page_setting_id' => (int) $page_setting->id,
                'type'            => PageSetting::ITEM_TYPE_FILTER,
                'code'            => (string) $payload['code'],
            ]);

            $was_existing_item = $item->exists;

            $item->fill([
                'source_type' => Arr::get($payload, 'source_type'),
                'source_id'   => Arr::get($payload, 'source_id'),
                'get'         => Arr::get($payload, 'get', []),
                'config'      => Arr::get($payload, 'config', []),
            ]);

            if (! $was_existing_item) {
                $item->fill([
                    'is_enabled' => (bool) Arr::get($payload, 'is_enabled', true),
                    'sort_order' => (int) Arr::get($payload, 'sort_order', 0),
                ]);
            }

            $item->save();

            if ($was_existing_item) {
                $updated_count++;
            } else {
                $created_count++;
            }
        }

        return [
            'created_count' => $created_count,
            'updated_count' => $updated_count,
            'removed_count' => $removed_count,
        ];
    }
}
