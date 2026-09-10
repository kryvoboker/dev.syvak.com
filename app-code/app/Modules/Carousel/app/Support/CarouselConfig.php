<?php

declare(strict_types=1);

namespace Modules\Carousel\Support;

use Illuminate\Support\Arr;

/**
 * Provides deterministic access to Carousel module config from module files.
 *
 * We intentionally read config directly from the module path to avoid coupling
 * module settings with global application config namespace.
 */
class CarouselConfig
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $config = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (is_array($this->config)) {
            return $this->config;
        }

        $config_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

        if (! is_file($config_path)) {
            $this->config = [];

            return $this->config;
        }

        /** @var mixed $config_data */
        $config_data = require $config_path;

        /** @var array<string, mixed> $config_data */
        $config_data = is_array($config_data) ? $config_data : [];

        $this->config = $config_data;

        return $this->config;
    }
}
