<?php

declare(strict_types=1);

namespace Modules\BankTransfer\Providers;

use Modules\BankTransfer\Support\BankTransferConfig;
use Nwidart\Modules\Support\ModuleServiceProvider;

class BankTransferServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'BankTransfer';

    /**
     * The lowercase version of the module name.
     */
    // phpcs:ignore
    protected string $nameLower = 'banktransfer';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(BankTransferConfig::class);
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
