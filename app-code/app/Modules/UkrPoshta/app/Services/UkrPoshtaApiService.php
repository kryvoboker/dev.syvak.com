<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\UkrPoshta\Support\UkrPoshtaConfig;
use RuntimeException;

readonly class UkrPoshtaApiService
{
    public function __construct(
        private UkrPoshtaConfig $config,
    ) {
    }

    /**
     * @throws ConnectionException
     * @return array<string, mixed>
     */
    public function getRegions(): array
    {
        return $this->call('get_regions_by_region_ua');
    }

    /**
     * @throws ConnectionException
     * @return array<string, mixed>
     */
    public function getDistricts(int $region_id): array
    {
        try {
            return $this->call('get_districts_by_region_id_and_district_ua', [
                'region_id' => $region_id,
            ]);
        } catch (ConnectionException $e) {
            Log::channel('stack')->error($e->getMessage(), $e->getTrace());

            return [];
        }
    }

    /**
     * @throws ConnectionException
     * @return array<string, mixed>
     */
    public function getCities(int $district_id): array
    {
        return $this->call('get_city_by_region_id_and_district_id_and_city_ua', [
            'district_id' => $district_id,
        ]);
    }

    /**
     * @throws ConnectionException
     * @return array<string, mixed>
     */
    public function getPostOffices(int $district_id): array
    {
        return $this->call('get_postoffices_by_postindex', [
            'pdDistrictId' => $district_id,
        ]);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @throws ConnectionException
     * @return array<string, mixed>
     */
    public function call(string $endpoint, array $query = []): array
    {
        sleep($this->getWaihtTimeout());

        $response = Http::baseUrl(rtrim($this->getApiUrl(), '/') . '/')
            ->timeout($this->getTimeout())
            ->acceptJson()
            ->asJson()
            ->withToken($this->getApiKey())
            ->get($endpoint, $query);

        return $this->normalizeResponse($response, $endpoint);
    }

    private function getApiUrl(): string
    {
        return (string)$this->config->get('api.url', 'https://www.ukrposhta.ua/address-classifier-ws/');
    }

    private function getApiKey(): string
    {
        return $this->config->getApiKey();
    }

    private function getTimeout(): int
    {
        return max(1, (int)$this->config->get('api.timeout', 30));
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
    private function normalizeResponse(Response $response, string $endpoint): array
    {
        if ($response->successful() === false) {
            throw new RuntimeException(
                sprintf(
                    "Ukr Poshta API request failed for endpont: %s\ncode: %s\nmessage: %s.",
                    $endpoint,
                    $response->status(),
                    $response->body(),
                ),
            );
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new RuntimeException(
                sprintf(
                    "Ukr Poshta API returned invalid payload for endpoint: %s\ncode: %s\nmessage: %s.",
                    $endpoint,
                    $response->status(),
                    $response->body(),
                ),
            );
        }

        $errors = Arr::wrap(data_get($payload, 'errors', data_get($payload, 'error', [])));
        $warnings = Arr::wrap(data_get($payload, 'warnings', data_get($payload, 'warning', [])));

        if ($errors !== [] || $warnings !== []) {
            throw new RuntimeException(sprintf(
                'Ukr Poshta API returned warnings or errors for [%s]: %s',
                $endpoint,
                Str::squish(implode(' ', array_map('strval', array_merge($warnings, $errors)))),
            ));
        }

        $data = data_get($payload, 'Entries.Entry', data_get($payload, 'data', []));

        if (!is_array($data)) {
            $data = [];
        }

        return [
            'success' => true,
            'data' => $data,
            'raw' => $payload,
        ];
    }
}
