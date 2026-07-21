<?php

declare(strict_types=1);

namespace Modules\Pickup\Services;

use App\Enums\Order\OrderDataKeyEnum;
use Modules\Pickup\Support\PickupConfig;

final class PickupModuleDataService
{
    public function __construct(
        private readonly PickupCheckoutDataService $checkout_data_service,
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
            [
                'placement' => $placement,
                'page_type' => $page_type,
                ...$this->checkout_data_service->getCheckoutData((string) app()->getLocale()),
                OrderDataKeyEnum::DeliveryMethod->value => PickupConfig::DELIVERY_METHOD,
            ],
        ];
    }
}
