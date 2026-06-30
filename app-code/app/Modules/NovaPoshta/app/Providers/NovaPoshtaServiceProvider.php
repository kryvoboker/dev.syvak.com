<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class NovaPoshtaServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'NovaPoshta';

    /**
     * The lowercase version of the module name.
     */
    // phpcs:ignore
    protected string $nameLower = 'novaposhta';
}
