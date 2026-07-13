<?php

declare(strict_types=1);

namespace Modules\Pickup\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PickupServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Pickup';

    /**
     * The lowercase version of the module name.
     */
    // phpcs:ignore
    protected string $nameLower = 'pickup';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    protected function registerTranslations(): void
    {
        $lang_path = module_path($this->name, 'resources/lang');

        if (is_dir($lang_path)) {
            $this->loadTranslationsFrom($lang_path, $this->nameLower);
            $this->loadJsonTranslationsFrom($lang_path);
        }
    }

    /**
     * Define module schedules.
     *
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
