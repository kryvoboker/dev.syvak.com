<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Support;

use Illuminate\Support\Arr;

/**
 * Provides deterministic access to the Nova Poshta module config.
 */
class NovaPoshtaConfig
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

        if (is_file($config_path) === false) {
            $this->config = [];

            return $this->config;
        }

        $config_data = require $config_path;

        $this->config = is_array($config_data) ? $config_data : [];

        return $this->config;
    }
}
