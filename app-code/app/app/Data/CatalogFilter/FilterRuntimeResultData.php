<?php

declare(strict_types=1);

namespace App\Data\CatalogFilter;

use Spatie\LaravelData\Data;

class FilterRuntimeResultData extends Data
{
    /**
     * @param  array<int, int>  $product_ids
     * @param  array<int, array<string, mixed>>  $groups
     * @param  array<string, mixed>  $price_range
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public array $product_ids,
        public int $total,
        public array $groups,
        public array $price_range,
        public array $meta,
    ) {
    }
}
