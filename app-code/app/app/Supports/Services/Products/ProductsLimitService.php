<?php

declare(strict_types=1);

namespace App\Supports\Services\Products;

use Illuminate\Support\Arr;

class ProductsLimitService
{
    /** @param array<string, mixed> $page_setting_settings */
    public static function getProductsCategoryLimit(array $page_setting_settings): int
    {
        return max(
            1,
            integer_value(Arr::get(
                $page_setting_settings,
                'pagination.products_per_page_limit',
                integer_value(config('app.page_settings.category.products_per_page_limit', 20)),
            )),
        );
    }
}
