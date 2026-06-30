<?php

declare(strict_types=1);

namespace Modules\Carousel\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class CarouselServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Carousel';

    /**
     * The lowercase version of the module name.
     */
    // phpcs:ignore
    protected string $nameLower = 'carousel';
}
