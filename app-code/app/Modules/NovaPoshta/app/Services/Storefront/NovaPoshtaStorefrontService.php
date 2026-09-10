<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Services\Storefront;

class NovaPoshtaStorefrontService
{
    public function __construct(
        private readonly NovaPoshtaCheckoutDataService $checkout_data_service,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolveForPlacement(string $placement, ?string $page_type = null): array
    {
        if ($page_type !== string_value(config('page-settings.page_type.checkout', 'checkout'))) {
            return [];
        }

        return [
            $this->checkout_data_service->buildModulePayload($placement, $page_type),
        ];
    }
}
