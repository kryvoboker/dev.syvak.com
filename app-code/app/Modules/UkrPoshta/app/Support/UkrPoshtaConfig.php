<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Support;

use App\Enums\Order\DeliveryMethodEnum;
use Illuminate\Support\Arr;
use RuntimeException;

class UkrPoshtaConfig
{
    public const DELIVERY_METHOD = DeliveryMethodEnum::UkrPoshta->value;

    public const string API_KEY_GLOBAL_CONFIG_KEY = 'ukrposhta.api_key';

    public const string DELIVERY_COST_GLOBAL_CONFIG_KEY = 'ukrposhta.delivery_cost';

    public const string IS_DELIVERY_COST_ENABLED_GLOBAL_CONFIG_KEY = 'ukrposhta.is_delivery_cost_enabled';

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
        $raw_api_key = get_global_config(self::API_KEY_GLOBAL_CONFIG_KEY, '');
        $api_key = trim(is_scalar($raw_api_key) ? (string) $raw_api_key : '');

        if ($api_key === '') {
            throw new RuntimeException('Ukr Poshta API key is not configured in global configs.');
        }

        return $api_key;
    }

    public function getDeliveryCost(): string
    {
        $raw_delivery_cost = get_global_config(self::DELIVERY_COST_GLOBAL_CONFIG_KEY, '0');
        $delivery_cost = trim(is_scalar($raw_delivery_cost) ? (string) $raw_delivery_cost : '');

        if ($delivery_cost === '') {
            return '0.00';
        }

        return number_format((float) $delivery_cost, 2, '.', '');
    }

    public function isDeliveryCostEnabled(): bool
    {
        return filter_var(get_global_config(self::IS_DELIVERY_COST_ENABLED_GLOBAL_CONFIG_KEY, false), FILTER_VALIDATE_BOOL);
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

        /** @var mixed $config_data */
        $config_data = require $config_path;

        /** @var array<string, mixed> $config_data */
        $config_data = is_array($config_data) ? $config_data : [];

        $this->config = $config_data;

        return $this->config;
    }
}
