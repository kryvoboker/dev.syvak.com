<?php

declare(strict_types=1);

namespace App\Services\Cart\Modules\Discount;

class GiftCertificateModule
{
    /**
     * Returns callback for totals pipeline.
     *
     * TODO:
     * - validate gift certificate
     * - apply certificate amount to totals
     * - add intermediate totals line for certificate adjustment
     */
    public function resolveCallback(): callable
    {
        return static fn (array $totals_data): array => $totals_data;
    }
}
