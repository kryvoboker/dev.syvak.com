<?php

declare(strict_types=1);

namespace App\Services\Catalogs\Products;

use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class ProductVariantContentPersistenceService
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function prepareForSave(array $data): array
    {
        $relationships = [
            'size_guide_data' => $data['size_guide_data'] ?? [],
            'composition_and_care_data' => $data['composition_and_care_data'] ?? [],
        ];

        unset($data['size_guide_data'], $data['composition_and_care_data']);

        return [
            'attributes' => $data,
            'relationships' => $relationships,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function hydrateFormData(Product|ProductVariant $product): array
    {
        $product->loadMissing(['sizeGuides', 'compositions', 'cares']);

        return [
            'size_guide_data' => [
                'translations' => $product->sizeGuides
                    ->mapWithKeys(fn ($guide): array => [
                        (string) $guide->language_id => [
                            'title' => $guide->short_title,
                            'short_description' => $guide->short_description,
                            'table_rows' => $guide->table_rows,
                            'image' => $guide->image,
                            'image_width' => $guide->image_width,
                            'image_height' => $guide->image_height,
                            'full_description_title' => $guide->full_description_title,
                            'full_description' => $guide->full_description,
                        ],
                    ])
                    ->all(),
            ],
            'composition_and_care_data' => [
                'translations' => $this->buildDetailsTranslations($product),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $relationship_data
     */
    public function syncRelations(Product|ProductVariant $product, array $relationship_data): void
    {
        $product->sizeGuides()->delete();
        $product->sizeGuides()->createMany($this->normalizeSizeGuides($relationship_data['size_guide_data'] ?? []));

        $product->compositions()->delete();
        $product->compositions()->createMany($this->normalizeDetails($relationship_data['composition_and_care_data'] ?? [], 'composition'));

        $product->cares()->delete();
        $product->cares()->createMany($this->normalizeDetails($relationship_data['composition_and_care_data'] ?? [], 'care'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSizeGuides(mixed $data): array
    {
        $translations = Arr::get(is_array($data) ? $data : [], 'translations', []);

        $normalized_guides = collect(is_array($translations) ? $translations : [])
            ->map(function (mixed $translation, int|string $language_id): ?array {
                if (! is_array($translation) || ! is_numeric($language_id)) {
                    return null;
                }

                $row = [
                    'language_id' => (int) $language_id,
                    'short_title' => $this->stringValue($translation['title'] ?? null),
                    'short_description' => $this->stringValue($translation['short_description'] ?? null),
                    'table_rows' => $this->stringValue($translation['table_rows'] ?? null),
                    'image' => $this->stringValue($translation['image'] ?? null),
                    'image_width' => $this->nullableInteger($translation['image_width'] ?? null),
                    'image_height' => $this->nullableInteger($translation['image_height'] ?? null),
                    'full_description_title' => $this->stringValue($translation['full_description_title'] ?? null),
                    'full_description' => $this->stringValue($translation['full_description'] ?? null),
                ];

                return collect($row)
                    ->except('language_id')
                    ->filter(fn (mixed $value): bool => filled($value))
                    ->isEmpty() ? null : $row;
            })
            ->filter()
            ->values()
            ->all();

        /** @var array<int, array<string, mixed>> $normalized_guides */
        return $normalized_guides;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeDetails(mixed $data, string $section): array
    {
        $translations = Arr::get(is_array($data) ? $data : [], 'translations', []);

        $normalized_details = collect(is_array($translations) ? $translations : [])
            ->map(function (mixed $translation, int|string $language_id) use ($section): ?array {
                if (! is_array($translation) || ! is_numeric($language_id)) {
                    return null;
                }

                $section_data = Arr::get($translation, $section, []);
                $items = collect((array) (is_array($section_data) ? ($section_data['items'] ?? []) : []))
                    ->filter(fn (mixed $item): bool => is_array($item) && filled($item['value'] ?? null))
                    ->map(fn (array $item): array => ['value' => Str::trim($this->stringValue($item['value']))])
                    ->values()
                    ->all();
                $title = $this->stringValue(is_array($section_data) ? ($section_data['title'] ?? null) : null);

                if ($title === '' && $items === []) {
                    return null;
                }

                return [
                    'language_id' => (int) $language_id,
                    'title' => $title,
                    'items' => $items,
                ];
            })
            ->filter()
            ->values()
            ->all();

        /** @var array<int, array<string, mixed>> $normalized_details */
        return $normalized_details;
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function buildDetailsTranslations(Product|ProductVariant $product): array
    {
        $translations = [];

        foreach ($product->compositions as $composition) {
            $translations[(string) $composition->language_id]['composition'] = [
                'title' => $composition->title,
                'items' => $composition->items ?? [],
            ];
        }

        foreach ($product->cares as $care) {
            $translations[(string) $care->language_id]['care'] = [
                'title' => $care->title,
                'items' => $care->items ?? [],
            ];
        }

        return $translations;
    }

    private function stringValue(mixed $value): string
    {
        return Str::trim(is_scalar($value) ? (string) $value : '');
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
