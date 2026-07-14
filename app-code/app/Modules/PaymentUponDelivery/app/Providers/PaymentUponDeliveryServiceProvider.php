<?php

declare(strict_types=1);

namespace Modules\PaymentUponDelivery\Providers;

use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PaymentUponDeliveryServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'PaymentUponDelivery';

    /**
     * The lowercase version of the module name.
     */
    // phpcs:ignore
    protected string $nameLower = 'paymentupondelivery';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(PaymentUponDeliveryConfig::class);
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
