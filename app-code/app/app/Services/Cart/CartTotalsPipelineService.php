<?php

declare(strict_types=1);

namespace App\Services\Cart;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class CartTotalsPipelineService
{
    /**
     * @param  array<int, array<string, mixed>>  $cart_items
     * @param  array<int, callable(array<string, mixed>): array<string, mixed>>  $callbacks
     * @return array<string, mixed>
     */
    public function calculate(array $cart_items, array $callbacks = []): array
    {
        $subtotal = collect($cart_items)
            ->sum(fn (array $item_data): float => (float) Arr::get($item_data, 'line_total', 0));

        $totals_data = [
            'lines' => [[
                'code'                   => 'items_subtotal',
                'label'                  => __('catalog/default.cart.totals.items_subtotal'),
                'amount'                 => $subtotal,
                'is_visible'             => true,
                'include_in_grand_total' => true,
            ]],
            'items_subtotal' => $subtotal,
            'grand_total'    => $subtotal,
            'currency_code'  => (string) config('app.currency.current_currency_code'),
            'exchange_rate'  => (float) config('app.currency.current_exchange_rate'),
        ];

        foreach ($callbacks as $callback) {
            if (! is_callable($callback)) {
                continue;
            }

            try {
                $result_data = $callback($totals_data);

                if (is_array($result_data)) {
                    $totals_data = $result_data;
                }
            } catch (Throwable $exception) {
                Log::channel('stack')->error($exception->getMessage(), [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]);
            }
        }

        $lines = collect((array) Arr::get($totals_data, 'lines', []))
            ->map(function (mixed $line_data): array {
                $line_data = is_array($line_data) ? $line_data : [];

                return [
                    'code'                   => (string) Arr::get($line_data, 'code', ''),
                    'label'                  => (string) Arr::get($line_data, 'label', ''),
                    'amount'                 => (float) Arr::get($line_data, 'amount', 0),
                    'is_visible'             => (bool) Arr::get($line_data, 'is_visible', true),
                    'include_in_grand_total' => (bool) Arr::get($line_data, 'include_in_grand_total', true),
                ];
            })
            ->filter(fn (array $line_data): bool => filled($line_data['code']) && filled($line_data['label']))
            ->values();

        $grand_total = $lines
            ->filter(fn (array $line_data): bool => $line_data['is_visible'] === true && $line_data['include_in_grand_total'] === true)
            ->sum(fn (array $line_data): float => (float) $line_data['amount']);

        $currency_code = (string) Arr::get($totals_data, 'currency_code', config('app.currency.current_currency_code'));
        $exchange_rate = (float) Arr::get($totals_data, 'exchange_rate', config('app.currency.current_exchange_rate'));

        $normalized_lines = $lines
            ->map(function (array $line_data) use ($currency_code, $exchange_rate): array {
                $line_data['formatted'] = $this->formatMoney(
                    (float) $line_data['amount'],
                    $currency_code,
                    $exchange_rate,
                );

                return $line_data;
            })
            ->all();

        return [
            'lines'                 => $normalized_lines,
            'items_subtotal'        => (float) Arr::get($totals_data, 'items_subtotal', 0),
            'grand_total'           => $grand_total,
            'grand_total_formatted' => $this->formatMoney($grand_total, $currency_code, $exchange_rate),
            'currency_code'         => $currency_code,
            'exchange_rate'         => $exchange_rate,
        ];
    }

    private function formatMoney(float $amount, string $currency_code, float $exchange_rate): string
    {
        return replace_currency_symbol_to_code(
            format_price($amount, $currency_code, $exchange_rate),
        );
    }
}
