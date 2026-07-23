<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Services\Storefront;

class UkrPoshtaStorefrontService
{
    public function __construct(
        private readonly UkrPoshtaCheckoutDataService $checkout_data_service,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolveForPlacement(string $placement, ?string $page_type = null): array
    {
        if ($page_type !== (string) config('page-settings.page_type.checkout', 'checkout')) {
            return [];
        }

        return [
            $this->checkout_data_service->buildModulePayload($placement, $page_type),
        ];
    }
}
