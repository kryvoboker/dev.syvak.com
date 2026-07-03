<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class UkrPoshtaServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'UkrPoshta';

    // phpcs:ignore
    protected string $nameLower = 'ukrposhta';

    protected function registerTranslations(): void
    {
        $lang_path = module_path($this->name, 'resources/lang');

        if (is_dir($lang_path)) {
            $this->loadTranslationsFrom($lang_path);
            $this->loadJsonTranslationsFrom($lang_path);
        }
    }
}
