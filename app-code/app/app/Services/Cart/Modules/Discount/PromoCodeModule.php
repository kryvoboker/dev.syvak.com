<?php

declare(strict_types=1);

namespace App\Services\Cart\Modules\Discount;

use App\Services\Marketing\PromoCodeService;

class PromoCodeModule
{
    /**
     * @param array<string, mixed> $context
     */
    public function resolveCallback(array $context = []): callable
    {
        return function (array $totals_data) use ($context): array {
            return app(PromoCodeService::class)->applyToTotals(
                totals_data: $totals_data,
                cart_items: (array) ($context['cart_items'] ?? []),
                code: filled($context['code'] ?? null) ? (string) $context['code'] : null,
                user_id: is_numeric($context['user_id'] ?? null) ? (int) $context['user_id'] : null,
                user_group_id: is_numeric($context['user_group_id'] ?? null) ? (int) $context['user_group_id'] : null,
                locale: filled($context['locale'] ?? null) ? (string) $context['locale'] : null,
            );
        };
    }
}
