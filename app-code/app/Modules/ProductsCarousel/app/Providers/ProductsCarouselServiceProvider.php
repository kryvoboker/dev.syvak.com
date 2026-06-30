<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ProductsCarouselServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'ProductsCarousel';

    /**
     * The lowercase version of the module name.
     */
    // phpcs:ignore
    protected string $nameLower = 'productscarousel';
}
