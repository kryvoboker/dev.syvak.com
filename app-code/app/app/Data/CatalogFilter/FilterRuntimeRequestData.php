<?php

declare(strict_types=1);

namespace App\Data\CatalogFilter;

use Spatie\LaravelData\Data;

class FilterRuntimeRequestData extends Data
{
    /**
     * @param  array<string, array<int, string>>  $selected_values
     */
    public function __construct(
        public string $filter_set_code,
        public string $context_type,
        public ?int $category_id,
        public array $selected_values,
        public ?float $price_from,
        public ?float $price_to,
        public bool $in_stock_only,
        public ?string $sort,
        public int $page,
        public int $per_page,
    ) {}
}
