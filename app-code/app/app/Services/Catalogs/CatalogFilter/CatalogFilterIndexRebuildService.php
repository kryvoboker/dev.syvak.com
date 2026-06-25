<?php

declare(strict_types=1);

namespace App\Services\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterContextTypeEnum;
use App\Enums\CatalogFilter\CatalogFilterDiscountOnlyPolicyEnum;
use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Enums\CatalogFilter\CatalogFilterIndexRunModeEnum;
use App\Enums\CatalogFilter\CatalogFilterIndexStatusEnum;
use App\Enums\CatalogFilter\CatalogFilterPriceSourceModeEnum;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterIndexMeta;
use App\Models\Catalogs\CatalogFilter\CatalogFilterProductIndex;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Models\Catalogs\CatalogFilter\CatalogFilterValue;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariantAttributeValue;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

readonly class CatalogFilterIndexRebuildService
{
    public function __construct(private PriceSourceResolverService $price_source_resolver_service)
    {
    }

    /**
     * @throws Throwable
     *
     * @return array<string, int|string>
     */
    public function rebuild(CatalogFilterSet $filter_set): array
    {
        /** @var CatalogFilterIndexMeta $index_meta */
        $index_meta = $filter_set->indexMeta()->firstOrCreate(
            ['catalog_filter_set_id' => (int) $filter_set->id],
            [
                'index_version'        => 1,
                'active_index_version' => 1,
                'last_status'          => CatalogFilterIndexStatusEnum::Ok->value,
                'last_run_mode'        => CatalogFilterIndexRunModeEnum::Full->value,
            ],
        );

        $started_at                   = now(config('app.timezone'));
        $rebuild_lock_timeout_seconds = $this->resolveRebuildLockTimeoutSeconds($filter_set);

        if (! $this->canAcquireRebuildLock($index_meta, $started_at, $rebuild_lock_timeout_seconds)) {
            return [
                'status'        => 'locked',
                'rows_total'    => (int) $index_meta->index_rows_total,
                'index_version' => (int) $index_meta->active_index_version,
            ];
        }

        $next_index_version = max(
            (int) $index_meta->index_version,
            (int) $index_meta->active_index_version,
            1,
        ) + 1;

        $index_meta->forceFill([
            'building_index_version'   => $next_index_version,
            'rebuild_lock_key'         => (string) Str::uuid(),
            'rebuild_lock_acquired_at' => $started_at,
            'last_status'              => CatalogFilterIndexStatusEnum::Running->value,
            'last_run_mode'            => CatalogFilterIndexRunModeEnum::Full->value,
            'last_progress_percent'    => 0,
            'last_error'               => null,
        ])->save();

        try {
            $indexed_rows_total = $this->buildRowsForIndexVersion($filter_set, $next_index_version, $started_at);

            CatalogFilterProductIndex::query()
                ->where('catalog_filter_set_id', (int) $filter_set->id)
                ->where('index_version', '!=', $next_index_version)
                ->delete();

            $enabled_groups_count = CatalogFilterGroup::query()
                ->where('catalog_filter_set_id', (int) $filter_set->id)
                ->where('is_enabled', true)
                ->count();

            $enabled_values_count = CatalogFilterValue::query()
                ->whereIn(
                    'catalog_filter_group_id',
                    CatalogFilterGroup::query()
                        ->where('catalog_filter_set_id', (int) $filter_set->id)
                        ->where('is_enabled', true)
                        ->pluck('id')
                        ->all(),
                )
                ->where('is_enabled', true)
                ->count();

            $finished_at = now(config('app.timezone'));

            $index_meta->forceFill([
                'index_version'            => $next_index_version,
                'active_index_version'     => $next_index_version,
                'building_index_version'   => null,
                'rebuild_lock_key'         => null,
                'rebuild_lock_acquired_at' => null,
                'last_full_rebuild_at'     => $finished_at,
                'last_status'              => CatalogFilterIndexStatusEnum::Ok->value,
                'last_error'               => null,
                'last_progress_percent'    => 100,
                'items_total'              => $enabled_groups_count,
                'values_total'             => $enabled_values_count,
                'index_rows_total'         => $indexed_rows_total,
            ])->save();

            return [
                'status'        => 'ok',
                'rows_total'    => $indexed_rows_total,
                'index_version' => $next_index_version,
            ];
        } catch (Throwable $throwable) {
            $index_meta->forceFill([
                'building_index_version'   => null,
                'rebuild_lock_key'         => null,
                'rebuild_lock_acquired_at' => null,
                'last_status'              => CatalogFilterIndexStatusEnum::Failed->value,
                'last_error'               => $throwable->getMessage(),
                'last_progress_percent'    => 0,
            ])->save();

            throw $throwable;
        }
    }

    private function canAcquireRebuildLock(
        CatalogFilterIndexMeta $index_meta,
        CarbonInterface $started_at,
        int $rebuild_lock_timeout_seconds,
    ): bool {
        if (blank($index_meta->rebuild_lock_key) || ! $index_meta->rebuild_lock_acquired_at) {
            return true;
        }

        $lock_acquired_at = Carbon::parse((string) $index_meta->getRawOriginal('rebuild_lock_acquired_at'));
        $lock_expires_at  = $lock_acquired_at->copy()->addSeconds($rebuild_lock_timeout_seconds);

        return $lock_expires_at->lte($started_at);
    }

    private function resolveRebuildLockTimeoutSeconds(CatalogFilterSet $filter_set): int
    {
        $settings                = (array) ($filter_set->settings ?? []);
        $default_timeout_seconds = (int) config('catalog-filter.defaults.rebuild_lock_timeout_seconds', 600);

        return max(1, (int) ($settings['rebuild_lock_timeout_seconds'] ?? $default_timeout_seconds));
    }

    /**
     * @throws Throwable
     */
    private function buildRowsForIndexVersion(
        CatalogFilterSet $filter_set,
        int $index_version,
        CarbonInterface $indexed_at,
    ): int {
        $groups = $this->resolveEnabledGroups($filter_set);

        CatalogFilterProductIndex::query()
            ->where('catalog_filter_set_id', (int) $filter_set->id)
            ->where('index_version', $index_version)
            ->delete();

        if ($groups->isEmpty()) {
            return 0;
        }

        /** @var Collection<int, CatalogFilterGroup> $attribute_groups */
        $attribute_groups = $groups->filter(
            fn (CatalogFilterGroup $group): bool => (string) $group->getRawOriginal('source_type') === CatalogFilterGroupSourceTypeEnum::Attribute->value,
        )->values();

        $attribute_ids = $attribute_groups
            ->map(fn (CatalogFilterGroup $group): int => (int) $group->source_id)
            ->filter(fn (int $attribute_id): bool => $attribute_id > 0)
            ->unique()
            ->values()
            ->all();

        $value_lookup_by_attribute = $this->buildAttributeValueLookup($attribute_groups);
        $minimum_stock_quantity    = max(0, (int) $filter_set->min_stock_quantity);

        $insert_chunk_size  = max(1, (int) config('catalog-filter.defaults.rebuild_chunk_size', 1000));
        $indexed_rows_total = 0;

        $this->buildProductsBaseQuery()->chunkById(200, function (Collection $products) use (
            $attribute_ids,
            $filter_set,
            $index_version,
            $indexed_at,
            $value_lookup_by_attribute,
            $minimum_stock_quantity,
            $insert_chunk_size,
            &$indexed_rows_total,
        ): void {
            /** @var Collection<int, Product> $products */
            $rows_to_insert = [];

            foreach ($products as $product) {
                $product_id   = (int) $product->id;
                $category_ids = collect($product->categories)
                    ->map(fn (mixed $category): int => (int) data_get($category, 'id', 0))
                    ->filter(fn (int $category_id): bool => $category_id > 0)
                    ->unique()
                    ->values();

                if ($category_ids->isEmpty()) {
                    continue;
                }

                $base_price = is_numeric($product->getAttribute('default_variant_price'))
                    ? (float) $product->getAttribute('default_variant_price')
                    : null;
                $discount_price = is_numeric($product->getAttribute('active_discount_price'))
                    ? (float) $product->getAttribute('active_discount_price')
                    : null;
                $effective_price = $this->price_source_resolver_service->resolveEffectivePrice(
                    rrc_price: $base_price ?? 0.0,
                    discount_price: $discount_price,
                    price_source_mode: $this->resolvePriceSourceMode($filter_set),
                    discount_only_policy: $this->resolveDiscountOnlyPolicy($filter_set),
                );

                $stock_quantity = (int) ($product->getAttribute('default_variant_quantity') ?? 0);
                $is_in_stock    = $stock_quantity >= $minimum_stock_quantity;

                foreach ($category_ids as $category_id) {
                    foreach (collect(optional($product->defaultVariant)->attributeValues) as $attribute_value) {
                        if (! $attribute_value instanceof ProductVariantAttributeValue) {
                            continue;
                        }

                        $attribute_id = (int) $attribute_value->attribute_id;

                        if ($attribute_id <= 0 || ! in_array($attribute_id, $attribute_ids, true)) {
                            continue;
                        }

                        $normalized_value = $this->normalizeAttributeValue((string) $attribute_value->value_string);

                        if (blank($normalized_value)) {
                            continue;
                        }

                        $group_value_map = $value_lookup_by_attribute[$attribute_id] ?? null;

                        if (! is_array($group_value_map)) {
                            continue;
                        }

                        $value_data = $group_value_map[$normalized_value] ?? null;

                        if (! is_array($value_data)) {
                            continue;
                        }

                        $rows_to_insert[] = $this->buildIndexRowPayload(
                            filter_set_id: (int) $filter_set->id,
                            index_version: $index_version,
                            category_id: $category_id,
                            product_id: $product_id,
                            group_id: (int) $value_data['group_id'],
                            value_id: (int) $value_data['value_id'],
                            attribute_id: $attribute_id,
                            base_price: $base_price,
                            discount_price: $discount_price,
                            effective_price: $effective_price,
                            stock_quantity: $stock_quantity,
                            is_in_stock: $is_in_stock,
                            is_active_product: (bool) $product->is_active,
                            indexed_at: $indexed_at,
                        );
                    }
                }
            }

            if ($rows_to_insert === []) {
                return;
            }

            $rows_to_insert = collect($rows_to_insert)
                ->unique(fn (array $row): string => implode(':', [
                    (string) $row['catalog_filter_set_id'],
                    (string) $row['index_version'],
                    (string) $row['category_id'],
                    (string) $row['product_id'],
                    (string) $row['catalog_filter_group_id'],
                    (string) ($row['catalog_filter_value_id'] ?? '0'),
                    (string) ($row['attribute_id'] ?? '0'),
                ]))
                ->values()
                ->all();

            foreach (array_chunk($rows_to_insert, $insert_chunk_size) as $insert_chunk) {
                CatalogFilterProductIndex::query()->insert($insert_chunk);
                $indexed_rows_total += count($insert_chunk);
            }
        }, 'products.id', 'id');

        return $indexed_rows_total;
    }

    /**
     * @return Collection<int, CatalogFilterGroup>
     */
    private function resolveEnabledGroups(CatalogFilterSet $filter_set): Collection
    {
        return CatalogFilterGroup::query()
            ->where('catalog_filter_set_id', (int) $filter_set->id)
            ->where('is_enabled', true)
            ->whereIn('source_type', [
                CatalogFilterGroupSourceTypeEnum::Price->value,
                CatalogFilterGroupSourceTypeEnum::Attribute->value,
            ])
            ->with([
                'values' => fn ($query) => $query
                    ->where('is_enabled', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with('translations'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Builder<Product>
     */
    private function buildProductsBaseQuery(): Builder
    {
        $app_settings     = get_app_settings();
        $current_datetime = now(config('app.timezone'));
        $db_prefix        = config('database.prefix');

        return Product::query()
            ->select('products.*')
            ->selectRaw($db_prefix . 'default_product_variant.price as default_variant_price')
            ->selectRaw($db_prefix . 'default_product_variant.quantity as default_variant_quantity')
            ->selectRaw($db_prefix . 'active_product_discount.price as active_discount_price')
            ->leftJoin('product_variants as default_product_variant', function (JoinClause $join): void {
                $join
                    ->on('default_product_variant.product_id', '=', 'products.id')
                    ->where('default_product_variant.is_default', true);
            })
            ->leftJoin('product_variant_discounts as active_product_discount', function (JoinClause $join) use ($app_settings, $current_datetime): void {
                $join
                    ->on('active_product_discount.product_variant_id', '=', 'default_product_variant.id')
                    ->where('active_product_discount.user_group_id', '=', (int) $app_settings->user_group_id)
                    ->where('active_product_discount.date_start', '<=', $current_datetime)
                    ->where('active_product_discount.date_end', '>=', $current_datetime);
            })
            ->where('products.is_active', true)
            ->where('default_product_variant.is_active', true)
            ->with([
                'categories:id',
                'defaultVariant.attributeValues' => fn ($query) => $query
                    ->select('id', 'product_variant_id', 'attribute_id', 'value_string'),
            ])
            ->orderBy('products.id');
    }

    /**
     * @param  Collection<int, CatalogFilterGroup>  $attribute_groups
     * @return array<int, array<string, array{group_id: int, value_id: int}>>
     */
    private function buildAttributeValueLookup(Collection $attribute_groups): array
    {
        $lookup = [];

        foreach ($attribute_groups as $group) {
            $attribute_id = (int) $group->source_id;

            if ($attribute_id <= 0) {
                continue;
            }

            foreach ($group->values as $value) {
                /**
                 * Keep value lookup locale-agnostic: index build reads variant
                 * attribute labels in different languages, so we index both
                 * canonical filter value and all translated labels.
                 */
                $value_candidates = collect([(string) $value->value_string])
                    ->merge(
                        $value->translations
                            ->pluck('label')
                            ->map(fn (mixed $label): string => (string) $label),
                    )
                    ->map(fn (string $label): string => $this->normalizeAttributeValue($label))
                    ->filter(fn (string $label): bool => filled($label))
                    ->unique()
                    ->values();

                foreach ($value_candidates as $candidate) {
                    if (isset($lookup[$attribute_id][$candidate])) {
                        continue;
                    }

                    $lookup[$attribute_id][$candidate] = [
                        'group_id' => (int) $group->id,
                        'value_id' => (int) $value->id,
                    ];
                }
            }
        }

        return $lookup;
    }

    private function normalizeAttributeValue(string $value): string
    {
        return trim($value);
    }

    private function resolvePriceSourceMode(CatalogFilterSet $filter_set): CatalogFilterPriceSourceModeEnum
    {
        $price_source_mode = $filter_set->price_source_mode;

        if ($price_source_mode instanceof CatalogFilterPriceSourceModeEnum) {
            return $price_source_mode;
        }

        return CatalogFilterPriceSourceModeEnum::tryFrom((string) $price_source_mode)
            ?? CatalogFilterPriceSourceModeEnum::Both;
    }

    private function resolveDiscountOnlyPolicy(CatalogFilterSet $filter_set): CatalogFilterDiscountOnlyPolicyEnum
    {
        $discount_only_policy = $filter_set->discount_only_policy;

        if ($discount_only_policy instanceof CatalogFilterDiscountOnlyPolicyEnum) {
            return $discount_only_policy;
        }

        return CatalogFilterDiscountOnlyPolicyEnum::tryFrom((string) $discount_only_policy)
            ?? CatalogFilterDiscountOnlyPolicyEnum::ExcludeWithoutDiscount;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIndexRowPayload(
        int $filter_set_id,
        int $index_version,
        int $category_id,
        int $product_id,
        int $group_id,
        int $value_id,
        ?int $attribute_id,
        ?float $base_price,
        ?float $discount_price,
        ?float $effective_price,
        int $stock_quantity,
        bool $is_in_stock,
        bool $is_active_product,
        CarbonInterface $indexed_at,
    ): array {
        return [
            'catalog_filter_set_id'   => $filter_set_id,
            'index_version'           => $index_version,
            'context_type'            => CatalogFilterContextTypeEnum::Category->value,
            'category_id'             => $category_id,
            'product_id'              => $product_id,
            'catalog_filter_group_id' => $group_id,
            'catalog_filter_value_id' => $value_id,
            'attribute_id'            => $attribute_id,
            'base_price'              => $base_price,
            'discount_price'          => $discount_price,
            'effective_price'         => $effective_price,
            'stock_quantity'          => $stock_quantity,
            'is_in_stock'             => $is_in_stock,
            'is_active_product'       => $is_active_product,
            'indexed_at'              => $indexed_at,
            'created_at'              => $indexed_at,
            'updated_at'              => $indexed_at,
        ];
    }
}
