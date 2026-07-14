<?php

declare(strict_types=1);

namespace Modules\WayForPay\Providers;

use Modules\WayForPay\Support\WayForPayConfig;
use Nwidart\Modules\Support\ModuleServiceProvider;

final class WayForPayServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'WayForPay';

    // phpcs:ignore
    protected string $nameLower = 'wayforpay';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(WayForPayConfig::class);
    }

    protected function registerTranslations(): void
    {
        $lang_path = module_path($this->name, 'resources/lang');

        if (is_dir($lang_path)) {
            $this->loadTranslationsFrom($lang_path, $this->nameLower);
            $this->loadJsonTranslationsFrom($lang_path);
        }
    }
}
