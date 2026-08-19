<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Enums\Marketing\PromoCodeDiscountBaseModeEnum;
use App\Enums\Marketing\PromoCodeDiscountTypeEnum;
use App\Enums\Marketing\PromoCodeLimitModeEnum;
use App\Enums\Marketing\PromoCodeTypeEnum;
use App\Models\ApplicationSettings\Currency;
use App\Models\Catalogs\Products\Product;
use App\Models\Marketing\PromoCode;
use App\Models\Marketing\PromoCodeDiscount;
use App\Models\Marketing\PromoCodeUsage;
use App\Models\Orders\Orders;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class PromoCodeService
{
    /**
     * @param array<string, mixed> $totals_data
     * @param array<int, array<string, mixed>> $cart_items
     * @return array<string, mixed>
     */
    public function applyToTotals(
        array $totals_data,
        array $cart_items,
        ?string $code,
        ?int $user_id = null,
        ?int $user_group_id = null,
        ?string $locale = null,
    ): array {
        $code = Str::squish(string_value($code));

        if ($code === '') {
            return $totals_data;
        }

        $promo_code = $this->resolve($code);

        if (! $promo_code instanceof PromoCode) {
            return $this->reject($totals_data, 'invalid');
        }

        $result = $this->validateAndCalculate(
            $promo_code,
            $totals_data,
            $cart_items,
            $user_id,
            $user_group_id,
            $locale,
        );

        if (($result['is_valid'] ?? false) !== true) {
            return $this->reject($totals_data, string_value($result['error_type'] ?? 'invalid'), string_value($result['message'] ?? ''));
        }

        $discount_amount = float_value($result['discount_amount'] ?? 0);

        if ($discount_amount <= 0) {
            return $totals_data;
        }

        $lines = list_value(Arr::get($totals_data, 'lines', []));
        $lines[] = [
            'code' => 'promo_code',
            'label' => __('storefront/default.cart.totals.promo_code', ['promo_code' => $promo_code->code]),
            'amount' => -$discount_amount,
            'is_visible' => true,
            'include_in_grand_total' => true,
        ];

        $totals_data['lines'] = $lines;
        $totals_data['promo_code'] = [
            'code' => $promo_code->code,
            'promo_code_id' => integer_value($promo_code->getKey()),
            'discount_amount' => $discount_amount,
            'discount_amount_formatted' => $this->formatMoney(
                $discount_amount,
                string_value(Arr::get($totals_data, 'currency_code', '')),
                float_value(Arr::get($totals_data, 'exchange_rate', 0)),
            ),
            'base_amount' => float_value($result['base_amount'] ?? 0),
            'eligible_items_subtotal' => float_value($result['eligible_items_subtotal'] ?? 0),
            'rrc_items_subtotal' => float_value($result['rrc_items_subtotal'] ?? 0),
            'has_discounted_products' => (bool) ($result['has_discounted_products'] ?? false),
            'is_valid' => true,
        ];

        return $totals_data;
    }

    public function resolve(string $code): ?PromoCode
    {
        $normalized_code = PromoCode::normalizeCode($code);

        if ($normalized_code === '') {
            return null;
        }

        return PromoCode::query()
            ->with([
                'discounts.currency',
                'users:id',
                'userGroups:id',
                'products:id',
                'categories:id',
                'errorTranslations',
            ])
            ->where('normalized_code', $normalized_code)
            ->first();
    }

    /**
     * @param array<string, mixed> $totals_data
     * @param array<int, array<string, mixed>> $cart_items
     * @return array<string, mixed>
     */
    public function validateAndCalculate(
        PromoCode $promo_code,
        array $totals_data,
        array $cart_items,
        ?int $user_id = null,
        ?int $user_group_id = null,
        ?string $locale = null,
    ): array {
        if (! $promo_code->isWithinActivePeriod()) {
            return $this->invalidResult('expired', $this->resolveErrorMessage($promo_code, 'expired', $locale));
        }

        if (! $this->isEligibleIdentity($promo_code, $user_id, $user_group_id)) {
            return $this->invalidResult('not_eligible', __('storefront/default.cart.errors.promo_not_eligible'));
        }

        $consumer_key = $this->resolveConsumerKey($user_id);

        if (! $this->hasAvailableUsage($promo_code, $user_id, $user_group_id, $consumer_key)) {
            return $this->invalidResult('usage_limit', $this->resolveErrorMessage($promo_code, 'usage_limit', $locale));
        }

        $current_total = float_value(Arr::get($totals_data, 'grand_total', 0));
        $minimum_order_amount = (float) ($promo_code->minimum_order_amount ?? 0);

        if ($current_total < $minimum_order_amount) {
            return $this->invalidResult('minimum_order', $this->resolveErrorMessage($promo_code, 'minimum_order', $locale));
        }

        $currency_code = string_value(Arr::get($totals_data, 'currency_code', config('app.currency.current_currency_code')));
        $item_totals = $this->resolveEligibleItemTotals($promo_code, $cart_items);
        $non_product_total = max(0, $current_total - float_value(Arr::get($totals_data, 'items_subtotal', 0)));
        $base_amount = (float) $item_totals['eligible_subtotal'] + (float) $non_product_total;

        if ($promo_code->promo_type !== PromoCodeTypeEnum::Super && $item_totals['has_discounted_products']) {
            $base_amount = $promo_code->discount_base_mode === PromoCodeDiscountBaseModeEnum::ExcludeDiscountedProducts
                ? (float) $item_totals['non_discounted_subtotal'] + (float) $non_product_total
                : (float) $item_totals['rrc_subtotal'] + (float) $non_product_total;
        }

        $base_amount = min(max(0, $base_amount), max(0, $current_total));
        $discount_value = $this->resolveDiscountValue($promo_code, $currency_code);
        $discount_amount = $promo_code->discount_type === PromoCodeDiscountTypeEnum::Percentage
            ? (float) $base_amount * ($discount_value / (float) 100)
            : min((float) $base_amount, $discount_value);

        return [
            'is_valid' => true,
            'promo_code' => $promo_code,
            'discount_amount' => round(max(0, $discount_amount), 4),
            'base_amount' => round($base_amount, 4),
            'eligible_items_subtotal' => round($item_totals['eligible_subtotal'], 4),
            'non_discounted_subtotal' => round($item_totals['non_discounted_subtotal'], 4),
            'rrc_items_subtotal' => round($item_totals['rrc_subtotal'], 4),
            'has_discounted_products' => $item_totals['has_discounted_products'],
            'currency_code' => $currency_code,
            'consumer_key' => $consumer_key,
        ];
    }

    /**
     * @psalm-suppress PossiblyUnusedReturnValue
     * @param PromoCode $promo_code
     * @param Orders    $order
     * @param int|null  $user_id
     * @param int|null  $user_group_id
     *
     * @throws Throwable
     * @return PromoCodeUsage
     */
    public function consume(PromoCode $promo_code, Orders $order, ?int $user_id = null, ?int $user_group_id = null): PromoCodeUsage
    {
        $consumer_key = $this->resolveConsumerKey($user_id);

        return DB::transaction(function () use ($promo_code, $order, $user_id, $user_group_id, $consumer_key): PromoCodeUsage {
            $usage = PromoCodeUsage::query()->firstOrCreate(
                [
                    'promo_code_id' => $promo_code->getKey(),
                    'order_id' => $order->getKey(),
                ],
                [
                    'user_id' => $user_id,
                    'user_group_id' => $user_group_id,
                    'consumer_key' => $consumer_key,
                    'used_at' => now(),
                ],
            );

            Log::channel('daily')->info('[PromoCodeService] promo code consumed', [
                'promo_code_id' => $promo_code->getKey(),
                'order_id' => $order->getKey(),
                'user_id' => $user_id,
                'user_group_id' => $user_group_id,
            ]);

            return $usage;
        });
    }

    /**
     * @param array<int, array<string, mixed>> $cart_items
     * @return array{eligible_subtotal:float, non_discounted_subtotal:float, rrc_subtotal:float, has_discounted_products:bool}
     */
    private function resolveEligibleItemTotals(PromoCode $promo_code, array $cart_items): array
    {
        $product_ids = $promo_code->products->modelKeys();
        $category_ids = $promo_code->categories->modelKeys();
        $has_scope = $product_ids !== [] || $category_ids !== [];
        $eligible_subtotal = 0.0;
        $non_discounted_subtotal = 0.0;
        $rrc_subtotal = 0.0;
        $has_discounted_products = false;

        $product_categories = $this->resolveProductCategories($cart_items, $category_ids);

        foreach ($cart_items as $item) {
            $current_line_total = float_value(Arr::get($item, 'line_total', 0));
            $product_id = integer_value(Arr::get($item, 'product_id', 0));
            $is_in_scope = ! $has_scope
                || in_array($product_id, $product_ids, true)
                || array_intersect($product_categories[$product_id] ?? [], $category_ids) !== [];

            if (! $is_in_scope) {
                continue;
            }

            $is_discounted = (bool) Arr::get($item, 'is_discounted', false);
            $rrc_line_total = float_value(Arr::get($item, 'rrc_line_total', $current_line_total));
            $eligible_subtotal += $current_line_total;
            $rrc_subtotal += $rrc_line_total;

            if ($is_discounted) {
                $has_discounted_products = true;
            } else {
                $non_discounted_subtotal += $current_line_total;
            }
        }

        return compact('eligible_subtotal', 'non_discounted_subtotal', 'rrc_subtotal', 'has_discounted_products');
    }

    /**
     * @param array<int, array<string, mixed>> $cart_items
     * @param array<int, int> $category_ids
     * @return array<int, array<int, int>>
     */
    private function resolveProductCategories(array $cart_items, array $category_ids): array
    {
        if ($category_ids === []) {
            return [];
        }

        $product_ids = collect($cart_items)->pluck('product_id')->filter()->unique()->values();

        return Product::query()
            ->with('categories:id')
            ->whereIn('id', $product_ids)
            ->get()
            ->toBase()
            ->mapWithKeys(fn (Product $product): array => [
                integer_value($product->getKey()) => $product->categories->modelKeys(),
            ])
            ->all();
    }

    private function isEligibleIdentity(PromoCode $promo_code, ?int $user_id, ?int $user_group_id): bool
    {
        $selected_user_ids = $promo_code->users->modelKeys();
        $selected_group_ids = $promo_code->userGroups->modelKeys();

        if ($selected_user_ids === [] && $selected_group_ids === []) {
            return true;
        }

        return ($user_id !== null && in_array($user_id, $selected_user_ids, true))
            || ($user_group_id !== null && in_array($user_group_id, $selected_group_ids, true));
    }

    private function hasAvailableUsage(PromoCode $promo_code, ?int $user_id, ?int $user_group_id, string $consumer_key): bool
    {
        $usage_query = PromoCodeUsage::query()->where('promo_code_id', $promo_code->getKey());

        if (
            $promo_code->global_usage_limit !== null
            && $promo_code->global_usage_limit > 0
            && (clone $usage_query)->count() >= $promo_code->global_usage_limit
        ) {
            return false;
        }

        if (
            $promo_code->all_users_usage_limit !== null && $promo_code->all_users_usage_limit > 0
            && (clone $usage_query)->where('consumer_key', $consumer_key)->count() >= $promo_code->all_users_usage_limit
        ) {
            return false;
        }

        $selected_user_ids = $promo_code->users->modelKeys();
        if (
            $promo_code->user_usage_limit !== null
            && $promo_code->user_usage_limit > 0
            && ($selected_user_ids === [] || ($user_id !== null && in_array($user_id, $selected_user_ids, true)))
        ) {
            $user_usage_query = clone $usage_query;
            $user_usage_count = $promo_code->user_limit_mode === PromoCodeLimitModeEnum::Shared && $selected_user_ids !== []
                ? $user_usage_query->whereIn('user_id', $selected_user_ids)->count()
                : $user_usage_query->where('consumer_key', $consumer_key)->count();

            if ($user_usage_count >= $promo_code->user_usage_limit) {
                return false;
            }
        }

        $selected_group_ids = $promo_code->userGroups->modelKeys();
        if (
            $promo_code->group_usage_limit !== null
            && $promo_code->group_usage_limit > 0
            && ($selected_group_ids === [] || ($user_group_id !== null && in_array($user_group_id, $selected_group_ids, true)))
        ) {
            $group_usage_query = clone $usage_query;
            $group_usage_count = $promo_code->group_limit_mode === PromoCodeLimitModeEnum::Shared && $selected_group_ids !== []
                ? $group_usage_query->whereIn('user_group_id', $selected_group_ids)->count()
                : $group_usage_query->where('user_group_id', $user_group_id)->count();

            if ($group_usage_count >= $promo_code->group_usage_limit) {
                return false;
            }
        }

        if ($promo_code->all_groups_usage_limit !== null && $promo_code->all_groups_usage_limit > 0) {
            $group_usage_query = clone $usage_query;
            $group_usage_count = $user_group_id !== null
                ? $group_usage_query->where('user_group_id', $user_group_id)->count()
                : $group_usage_query->where('consumer_key', $consumer_key)->count();

            if ($group_usage_count >= $promo_code->all_groups_usage_limit) {
                return false;
            }
        }

        return true;
    }

    private function resolveConsumerKey(?int $user_id): string
    {
        if ($user_id !== null) {
            return 'user:' . $user_id;
        }

        return 'session:' . session()->getId();
    }

    private function resolveDiscountValue(PromoCode $promo_code, string $currency_code): float
    {
        $target_currency = Currency::query()->where('code', $currency_code)->where('is_active', true)->first()
            ?? (new Currency())->getDefaultActiveCurrency();
        $default_currency = (new Currency())->getDefaultActiveCurrency();
        $discounts = $promo_code->discounts;
        $discount = $discounts->first(fn (PromoCodeDiscount $item): bool => integer_value($item->currency_id) === integer_value($target_currency?->getKey()));

        if ($discount === null) {
            $discount = $discounts->first(fn (PromoCodeDiscount $item): bool => integer_value($item->currency_id) === integer_value($default_currency?->getKey()));
        }

        if ($discount === null) {
            return 0.0;
        }

        if ($promo_code->discount_type === PromoCodeDiscountTypeEnum::Percentage || $target_currency === null || $default_currency === null) {
            return float_value($discount->value);
        }

        if (integer_value($discount->currency_id) === integer_value($target_currency->getKey())) {
            return float_value($discount->value);
        }

        return convert_price(float_value($discount->value), string_value($default_currency->code), string_value($target_currency->code));
    }

    private function resolveErrorMessage(PromoCode $promo_code, string $error_type, ?string $locale): string
    {
        $language_id = resolve_language_by_locale($locale ?? app()->getLocale())?->id;
        $translation = $promo_code->errorTranslations->firstWhere('language_id', $language_id);
        $field = match ($error_type) {
            'expired' => 'expired_message',
            'minimum_order' => 'minimum_order_message',
            default => 'usage_limit_message',
        };

        return filled($translation?->{$field})
            ? string_value($translation->{$field})
            : string_value(__('storefront/default.cart.errors.promo_' . $error_type));
    }

    /** @return array{is_valid:false,error_type:string,message:string} */
    private function invalidResult(string $error_type, string $message): array
    {
        return ['is_valid' => false, 'error_type' => $error_type, 'message' => $message];
    }

    /**
     * @param array<string, mixed> $totals_data
     * @return array<string, mixed>
     */
    private function reject(array $totals_data, string $error_type, string $message = ''): array
    {
        $totals_data['promo_code'] = [
            'is_valid' => false,
            'error_type' => $error_type,
            'message' => $message !== '' ? $message : __('storefront/default.cart.errors.promo_invalid'),
        ];

        return $totals_data;
    }

    private function formatMoney(float $amount, string $currency_code, float $exchange_rate): string
    {
        return replace_currency_symbol_to_code(format_price($amount, $currency_code, $exchange_rate));
    }


    /** @return array<int, mixed> */
}
