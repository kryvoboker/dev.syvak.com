<?php

declare(strict_types=1);

namespace App\Http\Requests\Ajax;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class CatalogFilterAjaxIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page'           => ['sometimes', 'integer', 'min:1'],
            'sort'           => ['nullable', 'string', 'max:120'],
            'price_from'     => ['nullable', 'numeric', 'min:0'],
            'price_to'       => ['nullable', 'numeric', 'min:0'],
            'attributes'     => ['sometimes', 'array'],
            'attributes.*'   => ['array'],
            'attributes.*.*' => ['string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $price_from = $this->input('price_from');
            $price_to   = $this->input('price_to');

            if (is_numeric($price_from) && is_numeric($price_to) && (float) $price_from > (float) $price_to) {
                $validator->errors()->add('price_from', __('catalog/default.errors.price_from'));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $normalized_data = [
            'sort'       => $this->query('sort'),
            'price_from' => $this->query('price_from'),
            'price_to'   => $this->query('price_to'),
            'attributes' => [],
        ];

        $page = $this->query('page');

        if ($page !== null && $page !== '') {
            $normalized_data['page'] = $page;
        }

        $filter_set = $this->resolveActiveCategoryFilterSet();

        if ($filter_set instanceof CatalogFilterSet) {
            foreach ($filter_set->groups as $group) {
                if (! $group->is_enabled) {
                    continue;
                }

                if (
                    (string) $group->getRawOriginal('source_type') === CatalogFilterGroupSourceTypeEnum::Price->value
                    && ! $filter_set->is_price_filter_enabled
                ) {
                    continue;
                }

                if (
                    (string) $group->getRawOriginal('source_type') === CatalogFilterGroupSourceTypeEnum::Attribute->value
                    && ! $filter_set->is_attribute_filtering_enabled
                ) {
                    continue;
                }

                $this->normalizeGroupInput($group, $normalized_data);
            }
        }

        if ($normalized_data['attributes'] === []) {
            unset($normalized_data['attributes']);
        }

        $this->merge($normalized_data);
    }

    /**
     * @param  array<string, mixed>  $normalized_data
     */
    private function normalizeGroupInput(CatalogFilterGroup $group, array &$normalized_data): void
    {
        $source_type     = (string) $group->getRawOriginal('source_type');
        $group_get_key   = (string) $group->get_key;
        $group_config    = is_array($group->config) ? $group->config : [];
        $group_get_value = trim((string) Arr::get($group_config, 'get.value', ''));

        if ($source_type === CatalogFilterGroupSourceTypeEnum::Price->value) {
            $from_key = (string) Arr::get($group_config, 'get.extra.from_key', 'price_from');
            $to_key   = (string) Arr::get($group_config, 'get.extra.to_key', 'price_to');

            $normalized_data['price_from'] = $this->extractByGetKey($from_key) ?? $normalized_data['price_from'];
            $normalized_data['price_to']   = $this->extractByGetKey($to_key) ?? $normalized_data['price_to'];

            return;
        }

        $group_input_value  = $this->extractByGetKey($group_get_key);
        $group_input_values = $this->normalizeToStringArray($group_input_value);
        $query_key_exists   = $this->hasQueryKey($group_get_key);

        if (
            $group_input_values !== []
            && filled($group_get_value)
            && count($group_input_values) === 1
            && in_array(mb_strtolower($group_input_values[0]), ['1', 'true', 'on', 'yes'], true)
        ) {
            $group_input_values = [$group_get_value];
        }

        if (
            $group_input_values === []
            && $query_key_exists
            && filled($group_get_value)
        ) {
            // If key is present without meaningful value, use configured fallback contract value.
            $group_input_values = [$group_get_value];
        }

        if ($group_input_values === []) {
            return;
        }

        if ($source_type === CatalogFilterGroupSourceTypeEnum::Attribute->value) {
            $attribute_id = (int) $group->source_id;

            if ($attribute_id > 0) {
                $normalized_data['attributes'][$attribute_id] = $group_input_values;
            }
        }
    }

    private function extractByGetKey(string $get_key): mixed
    {
        if (blank($get_key)) {
            return null;
        }

        if (preg_match('/^(?<root>[a-zA-Z0-9_]+)\[(?<child>[a-zA-Z0-9_]+)]$/', $get_key, $matches) === 1) {
            return data_get($this->query(), $matches['root'] . '.' . $matches['child']);
        }

        return $this->query($get_key);
    }

    private function hasQueryKey(string $get_key): bool
    {
        if (blank($get_key)) {
            return false;
        }

        if (preg_match('/^(?<root>[a-zA-Z0-9_]+)\[(?<child>[a-zA-Z0-9_]+)]$/', $get_key, $matches) === 1) {
            return Arr::has($this->query(), $matches['root'] . '.' . $matches['child']);
        }

        return Arr::has($this->query(), $get_key);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeToStringArray(mixed $value): array
    {
        if (! is_array($value)) {
            $value = [$value];
        }

        return collect($value)
            ->flatten(1)
            ->flatMap(function (mixed $item): array {
                // Support both query styles:
                // - ?weight[]=light&weight[]=medium
                // - ?weight=light,medium
                $string_item = trim((string) $item);

                if (blank($string_item)) {
                    return [];
                }

                return collect(explode(',', $string_item))
                    ->map(fn (string $part): string => trim($part))
                    ->filter(fn (string $part): bool => filled($part))
                    ->values()
                    ->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    private function resolveActiveCategoryFilterSet(): ?CatalogFilterSet
    {
        return CatalogFilterSet::query()
            ->where('is_enabled', true)
            ->where(function ($query): void {
                $query
                    ->where('context_type', 'category')
                    ->orWhereJsonContains('context_types', 'category');
            })
            ->with('groups')
            ->orderBy('id')
            ->first();
    }
}
