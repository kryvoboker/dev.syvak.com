<?php

declare(strict_types=1);

namespace Modules\Pickup\Services\Storefront;

use App\Enums\Order\OrderDataKeyEnum;
use Modules\Pickup\Support\PickupConfig;

final class PickupStorefrontService
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
        $checkout_page_type = config('page-settings.page_type.checkout', 'checkout');

        if ($page_type !== (is_scalar($checkout_page_type) ? (string) $checkout_page_type : 'checkout')) {
            return [];
        }

        $checkout_data = $this->checkout_data_service->getCheckoutData((string) app()->getLocale());
        unset($checkout_data[OrderDataKeyEnum::DeliveryMethod->value]);

        return [
            [
                'placement' => $placement,
                'page_type' => $page_type,
                ...$checkout_data,
                OrderDataKeyEnum::DeliveryMethod->value => PickupConfig::DELIVERY_METHOD,
            ],
        ];
    }
}
