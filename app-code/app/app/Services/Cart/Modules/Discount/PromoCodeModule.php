<?php

declare(strict_types=1);

namespace App\Services\Cart\Modules\Discount;

class PromoCodeModule
{
    /**
     * Returns callback for totals pipeline.
     *
     * TODO:
     * - validate promo code against active rules
     * - calculate discount amount
     * - add intermediate totals line for promo discount
     */
    public function resolveCallback(): callable
    {
        return static fn (array $totals_data): array => $totals_data;
    }
}
