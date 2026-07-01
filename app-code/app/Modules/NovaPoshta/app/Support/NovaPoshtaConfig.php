<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Support;

use Illuminate\Support\Arr;
use RuntimeException;

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

    public function getApiKey(): string
    {
        $api_key = trim((string) get_global_config('novaposhta.api_key', ''));

        if ($api_key === '') {
            throw new RuntimeException('Nova Poshta API key is not configured in global configs.');
        }

        return $api_key;
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
