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
                cart_items: $this->arrayRows($context['cart_items'] ?? []),
                code: filled($context['code'] ?? null) ? $this->stringValue($context['code']) : null,
                user_id: is_numeric($context['user_id'] ?? null) ? (int) $context['user_id'] : null,
                user_group_id: is_numeric($context['user_group_id'] ?? null) ? (int) $context['user_group_id'] : null,
                locale: filled($context['locale'] ?? null) ? $this->stringValue($context['locale']) : null,
            );
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function arrayRows(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = [];

            foreach ($item as $key => $field) {
                if (is_string($key)) {
                    $row[$key] = $field;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
