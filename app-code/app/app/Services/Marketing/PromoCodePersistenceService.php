<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Models\ApplicationSettings\Currency;
use App\Models\Marketing\PromoCode;
use App\Models\Marketing\PromoCodeDiscount;
use App\Models\Marketing\PromoCodeErrorTranslation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PromoCodePersistenceService
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function prepareForSave(array $data, ?PromoCode $ignore = null): array
    {
        $code = Str::squish($this->stringValue(Arr::get($data, 'code', '')));
        $normalized_code = PromoCode::normalizeCode($code);
        $ignore_id = $ignore?->getKey();

        $duplicate_exists = PromoCode::query()
            ->where('normalized_code', $normalized_code)
            ->when($ignore_id !== null, fn ($query): mixed => $query->where('id', '!=', $ignore_id))
            ->exists();

        if ($duplicate_exists) {
            throw ValidationException::withMessages([
                'data.code' => __('validation.unique', ['attribute' => __('admin/marketing/promo_codes.labels.code')]),
            ]);
        }

        $data['code'] = $code;
        $data['normalized_code'] = $normalized_code;

        $default_currency = (new Currency())->getDefaultActiveCurrency();
        $discount_currency_ids = collect((array) ($data['discount_items'] ?? []))
            ->pluck('currency_id')
            ->filter(fn (mixed $currency_id): bool => is_numeric($currency_id))
            ->map(fn (mixed $currency_id): int => (int) $currency_id)
            ->all();

        if ($default_currency === null || ! in_array($this->integerValue($default_currency->getKey()), $discount_currency_ids, true)) {
            throw ValidationException::withMessages([
                'data.discount_items' => __('admin/marketing/promo_codes.errors.default_currency_required'),
            ]);
        }

        return $this->extractRelationshipState($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function hydrateFormData(PromoCode $promo_code): array
    {
        $promo_code->loadMissing([
            'discounts',
            'users',
            'userGroups',
            'products',
            'categories',
            'errorTranslations',
        ]);

        /** @var Collection<int, PromoCodeDiscount> $discounts */
        $discounts = $promo_code->discounts;
        /** @var Collection<int, PromoCodeErrorTranslation> $error_translations */
        $error_translations = $promo_code->errorTranslations;

        return [
            'selected_users' => $promo_code->users->map(fn ($user): array => ['user_id' => $user->getKey()])->all(),
            'user_limit_scope' => $promo_code->users->isNotEmpty() ? 'selected_users' : 'all_users',
            'group_limit_scope' => $promo_code->userGroups->isNotEmpty() ? 'selected_groups' : 'all_groups',
            'selected_user_groups' => $promo_code->userGroups->map(fn ($group): array => ['user_group_id' => $group->getKey()])->all(),
            'product_items' => $promo_code->products->map(fn ($product): array => ['product_id' => $product->getKey()])->all(),
            'category_items' => $promo_code->categories->map(fn ($category): array => ['category_id' => $category->getKey()])->all(),
            'discount_items' => $discounts->map(fn (PromoCodeDiscount $discount): array => [
                'currency_id' => $discount->currency_id,
                'value' => $discount->value,
            ])->all(),
            'error_messages' => $error_translations
                ->mapWithKeys(fn (PromoCodeErrorTranslation $translation): array => [
                    $this->stringValue($translation->language_id) => [
                        'expired_message' => $translation->expired_message,
                        'minimum_order_message' => $translation->minimum_order_message,
                        'usage_limit_message' => $translation->usage_limit_message,
                    ],
                ])
                ->all(),
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function syncRelations(PromoCode $promo_code, array $data): void
    {
        $promo_code->users()->sync($this->idsFromSelect($data['selected_users'] ?? [], 'user_id'));
        $promo_code->userGroups()->sync($this->idsFromSelect($data['selected_user_groups'] ?? [], 'user_group_id'));
        $promo_code->products()->sync($this->idsFromRepeater($data['product_items'] ?? [], 'product_id'));
        $promo_code->categories()->sync($this->idsFromRepeater($data['category_items'] ?? [], 'category_id'));

        $promo_code->discounts()->delete();
        $promo_code->discounts()->createMany($this->normalizeDiscounts($this->arrayRows($data['discount_items'] ?? [])));

        $promo_code->errorTranslations()->delete();
        $promo_code->errorTranslations()->createMany($this->normalizeErrorTranslations($this->arrayRows($data['error_messages'] ?? [])));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function extractRelationshipState(array &$data): array
    {
        $relationship_data = [
            'selected_users' => $data['selected_users'] ?? [],
            'selected_user_groups' => $data['selected_user_groups'] ?? [],
            'product_items' => $data['product_items'] ?? [],
            'category_items' => $data['category_items'] ?? [],
            'discount_items' => $data['discount_items'] ?? [],
            'error_messages' => $data['error_messages'] ?? [],
            'user_limit_scope' => $data['user_limit_scope'] ?? 'all_users',
            'group_limit_scope' => $data['group_limit_scope'] ?? 'all_groups',
        ];

        if ($relationship_data['user_limit_scope'] === 'all_users') {
            $relationship_data['selected_users'] = [];
            $data['user_usage_limit'] = null;
            $data['user_limit_mode'] = null;
        } else {
            $data['all_users_usage_limit'] = null;
        }

        if ($relationship_data['group_limit_scope'] === 'all_groups') {
            $relationship_data['selected_user_groups'] = [];
            $data['group_usage_limit'] = null;
            $data['group_limit_mode'] = null;
        } else {
            $data['all_groups_usage_limit'] = null;
        }

        unset($data['user_limit_scope'], $data['group_limit_scope']);
        unset($relationship_data['user_limit_scope'], $relationship_data['group_limit_scope']);

        foreach (array_keys($relationship_data) as $key) {
            unset($data[$key]);
        }

        return ['attributes' => $data, 'relationships' => $relationship_data];
    }

    /** @return array<int, int> */
    private function idsFromSelect(mixed $values, ?string $item_key = null): array
    {
        return collect(is_array($values) ? $values : [$values])
            ->map(fn (mixed $value): mixed => is_array($value) && $item_key !== null ? ($value[$item_key] ?? null) : $value)
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, int> */
    private function idsFromRepeater(mixed $values, string $key): array
    {
        return collect(is_array($values) ? $values : [])
            ->map(fn (mixed $item): mixed => is_array($item) ? ($item[$key] ?? null) : null)
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array{currency_id:int, value:mixed}>
     */
    private function normalizeDiscounts(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => filled($item['currency_id'] ?? null))
            ->map(fn (array $item): array => [
                'currency_id' => $this->integerValue($item['currency_id']),
                'value' => $item['value'] ?? 0,
            ])
            ->unique('currency_id')
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $translations
     * @return array<int, array<string, mixed>>
     */
    private function normalizeErrorTranslations(array $translations): array
    {
        $normalized_translations = collect($translations)
            ->map(function (mixed $messages, int|string $language_id): ?array {
                if ($language_id < 1) {
                    return null;
                }

                $messages = collect($messages)->only([
                    'expired_message',
                    'minimum_order_message',
                    'usage_limit_message',
                ])->map(fn (mixed $message): ?string => filled($message) ? Str::squish($this->stringValue($message)) : null)->all();

                if (collect($messages)->filter(fn (mixed $message): bool => filled($message))->isEmpty()) {
                    return null;
                }

                return ['language_id' => $this->integerValue($language_id), ...$messages];
            })
            ->filter()
            ->values()
            ->all();

        /** @var array<int, array<string, mixed>> $normalized_translations */
        return $normalized_translations;
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

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
