<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Attributes\Attribute;
use App\Models\PageSettings\PageSetting;
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

            return $this->syncFilterItems($page_setting, $filter_item_payloads);
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
        $default_language = (new Language())->getDefaultLanguage();

        if ($default_language !== null) {
            return (int) $default_language->id;
        }

        $first_active_language = (new Language())->getActiveLanguages()->first();

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

        $base_price_stats = DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('products.is_active', true)
            ->where('product_variants.is_active', true)
            ->selectRaw('MIN(product_variants.price) as min_price, MAX(product_variants.price) as max_price')
            ->first();

        $discount_price_stats = DB::table('product_variant_discounts')
            ->where('date_start', '<=', $now)
            ->where('date_end', '>=', $now)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('product_variants')
                    ->join('products', 'products.id', '=', 'product_variants.product_id')
                    ->whereColumn('product_variants.id', 'product_variant_discounts.product_variant_id')
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
            ->whereHas('productVariantAttributeValues.variant.product', function ($query): void {
                $query->where('products.is_active', true);
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
        $settings = is_array($page_setting->settings) ? $page_setting->settings : [];

        $existing_filter_items = collect((array) Arr::get($settings, 'items.filters', []))
            ->filter(fn (mixed $item): bool => is_array($item) && filled((string) Arr::get($item, 'code')))
            ->keyBy(fn (array $item): string => (string) Arr::get($item, 'code'));

        $created_count = 0;
        $updated_count = 0;
        $next_items    = [];

        foreach ($payloads as $payload) {
            $code = (string) Arr::get($payload, 'code', '');

            if ($code === '') {
                continue;
            }

            $existing_item = $existing_filter_items->get($code);
            $is_existing   = is_array($existing_item);

            $next_items[] = [
                'code'        => $code,
                'source_type' => Arr::get($payload, 'source_type'),
                'source_id'   => Arr::get($payload, 'source_id'),
                'is_enabled'  => (bool) Arr::get($existing_item, 'is_enabled', Arr::get($payload, 'is_enabled', true)),
                'sort_order'  => (int) Arr::get($payload, 'sort_order', Arr::get($existing_item, 'sort_order', 0)),
                'get'         => [
                    'key'   => (string) Arr::get($payload, 'get.key', ''),
                    'value' => Arr::get($payload, 'get.value'),
                    'extra' => is_array(Arr::get($payload, 'get.extra')) ? Arr::get($payload, 'get.extra') : [],
                ],
                'config' => is_array(Arr::get($payload, 'config')) ? Arr::get($payload, 'config') : [],
            ];

            if ($is_existing) {
                $updated_count++;
            } else {
                $created_count++;
            }
        }

        $removed_count = max(0, $existing_filter_items->count() - count($next_items));

        Arr::set(
            $settings,
            'items.filters',
            collect($next_items)
                ->sortBy('sort_order')
                ->values()
                ->all(),
        );

        Arr::set($settings, 'meta.contract_version', max(2, (int) Arr::get($settings, 'meta.contract_version', 1)));

        $page_setting->forceFill([
            'settings' => $settings,
        ])->save();

        return [
            'created_count' => $created_count,
            'updated_count' => $updated_count,
            'removed_count' => $removed_count,
        ];
    }
}
