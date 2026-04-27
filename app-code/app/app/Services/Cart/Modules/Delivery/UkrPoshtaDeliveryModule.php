<?php

declare(strict_types=1);

namespace App\Services\Cart\Modules\Delivery;

class UkrPoshtaDeliveryModule
{
    /**
     * Returns callback for totals pipeline.
     *
     * TODO:
     * - resolve customer delivery params
     * - calculate UkrPoshta tariff
     * - add intermediate totals line for delivery if enabled
     */
    public function resolveCallback(): callable
    {
        return static fn (array $totals_data): array => $totals_data;
    }
}
