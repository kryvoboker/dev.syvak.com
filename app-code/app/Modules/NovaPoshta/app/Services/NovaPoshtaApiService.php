<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\NovaPoshta\Support\NovaPoshtaConfig;
use RuntimeException;

readonly class NovaPoshtaApiService
{
    public function __construct(
        private NovaPoshtaConfig $config,
    ) {
    }

    /**
     * @throws ConnectionException
     * @return array<string, mixed>
     */
    public function getRegions(): array
    {
        return $this->call('AddressGeneral', 'getSettlementAreas', [
            'Language' => $this->getLanguage(),
        ]);
    }

    /**
     * @throws ConnectionException
     * @return array<string, mixed>
     */
    public function getCities(int $page = 1): array
    {
        return $this->call('AddressGeneral', 'getSettlements', [
            'Language' => $this->getLanguage(),
            'Page' => $page,
            'Limit' => $this->getLimit(),
        ]);
    }

    /**
     * @throws ConnectionException
     * @return array<string, mixed>
     */
    public function getWarehouses(int $page = 1): array
    {
        return $this->call('AddressGeneral', 'getWarehouses', [
            'Language' => $this->getLanguage(),
            'Page' => $page,
            'Limit' => $this->getLimit(),
        ]);
    }

    /**
     * @param array<string, mixed> $method_properties
     *
     * @throws ConnectionException
     * @return array<string, mixed>
     */
    public function call(string $model_name, string $called_method, array $method_properties = []): array
    {
        sleep($this->getWaihtTimeout());

        $response = Http::timeout($this->getTimeout())
            ->acceptJson()
            ->asJson()
            ->post($this->getApiUrl(), [
                'apiKey' => $this->getApiKey(),
                'modelName' => $model_name,
                'calledMethod' => $called_method,
                'methodProperties' => $method_properties,
            ]);

        return $this->normalizeResponse($response, $model_name, $called_method);
    }

    private function getApiUrl(): string
    {
        return (string) $this->config->get('api.url', 'https://api.novaposhta.ua/v2.0/json/');
    }

    private function getApiKey(): string
    {
        return $this->config->getApiKey();
    }

    private function getLanguage(): string
    {
        return (string) $this->config->get('api.language', 'UA');
    }

    private function getLimit(): int
    {
        return max(1, (int) $this->config->get('api.limit', 500));
    }

    private function getTimeout(): int
    {
        return max(1, (int) $this->config->get('api.timeout', 30));
    }

    /**
     * @return int
     */
    private function getWaihtTimeout(): int
    {
        return max(1, (int)$this->config->get('api.wait_timeout', 1));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeResponse(Response $response, string $model_name, string $called_method): array
    {
        if ($response->successful() === false) {
            throw new RuntimeException(sprintf('Nova Poshta API request failed for %s::%s.', $model_name, $called_method));
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('Nova Poshta API returned invalid payload for %s::%s.', $model_name, $called_method));
        }

        $warnings = Arr::wrap($payload['warnings'] ?? []);
        $errors = Arr::wrap($payload['errors'] ?? []);

        if ($warnings !== [] || $errors !== []) {
            throw new RuntimeException(sprintf(
                'Nova Poshta API reported warnings or errors for %s::%s: %s',
                $model_name,
                $called_method,
                Str::squish(implode(' ', array_merge(array_map('strval', $warnings), array_map('strval', $errors)))),
            ));
        }

        return [
            'success' => (bool) Arr::get($payload, 'success', false),
            'data' => is_array(Arr::get($payload, 'data')) ? Arr::get($payload, 'data') : [],
            'info' => is_array(Arr::get($payload, 'info')) ? Arr::get($payload, 'info') : [],
            'raw' => $payload,
        ];
    }
}
