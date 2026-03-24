<?php

declare(strict_types=1);

namespace App\Supports\Services\Products;

use Illuminate\Support\Arr;

class ProductsLimitService
{
    public static function getProductsCategoryLimit(array $page_setting_settings): int
    {
        return max(
            1,
            (int) Arr::get(
                $page_setting_settings,
                'pagination.products_per_page_limit',
                (int) config('app.page_settings.category.products_per_page_limit', 20),
            ),
        );
    }
}
