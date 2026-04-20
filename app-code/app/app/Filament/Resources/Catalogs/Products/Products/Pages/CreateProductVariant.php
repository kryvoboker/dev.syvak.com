<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Catalogs\Products\Products\ProductVariantResource;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Slug;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use LogicException;

class CreateProductVariant extends CreateRecord
{
    protected static string $resource = ProductVariantResource::class;

    #[Locked]
    public ?int $product_id = null;

    public function mount(): void
    {
        $this->product_id = request()->integer('product');

        abort_if($this->product_id === null || $this->product_id < 1, 404);

        parent::mount();
    }

    public function form(Schema $schema): Schema
    {
        return ProductVariantResource::form($schema);
    }

    /**
     * @throws ValidationException
     */
    protected function beforeCreate(): void
    {
        $this->validateVariantSlugs();
        $this->validateVariantAttributesUniqueness();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['product_id'] = $this->product_id;

        if ((bool) ($data['is_default'] ?? false)) {
            ProductVariant::query()
                ->where('product_id', $this->product_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ProductVariantResource::getUrl('index', ['product' => $this->product_id]);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->url(ProductResource::getUrl('edit', ['record' => $this->getProductRecord()]));
    }

    public function getTitle(): string
    {
        return __('admin/catalogs/products/products.pages.create_variant');
    }

    public function getHeading(): ?string
    {
        return $this->getTitle();
    }

    private function getProductRecord(): Product
    {
        $product = Product::query()->find($this->product_id);

        if (! $product instanceof Product) {
            throw new LogicException('Product record is not initialized.');
        }

        return $product;
    }

    /**
     * @throws ValidationException
     */
    private function validateVariantSlugs(): void
    {
        $slug_rows = collect((array) data_get($this->data, 'slugs', []))
            ->filter(fn (mixed $row): bool => is_array($row))
            ->values();

        if ($slug_rows->isEmpty()) {
            return;
        }

        $language_ids = $slug_rows
            ->map(fn (array $row): int => (int) ($row['language_id'] ?? 0))
            ->filter(fn (int $language_id): bool => $language_id > 0)
            ->values();

        if ($language_ids->count() !== $language_ids->unique()->count()) {
            throw ValidationException::withMessages([
                'slugs' => __('admin/catalogs/products/products.errors.duplicate_variant_slug_language'),
            ]);
        }

        foreach ($slug_rows as $row_index => $slug_row) {
            $language_id = (int) ($slug_row['language_id'] ?? 0);
            $slug_value  = Str::of((string) ($slug_row['slug'] ?? ''))->trim()->toString();

            if ($language_id < 1 || $slug_value === '') {
                continue;
            }

            $slug_exists = Slug::query()
                ->where('slug', $slug_value)
                ->whereIn('sluggable_type', [Product::class, ProductVariant::class])
                ->exists();

            if ($slug_exists) {
                throw ValidationException::withMessages([
                    "slugs.$row_index.slug" => __('admin/catalogs/products/products.errors.duplicate_product_or_variant_slug_value'),
                ]);
            }
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateVariantAttributesUniqueness(): void
    {
        $requested_signatures = $this->buildVariantAttributeSignaturesByLanguage((array) $this->data);

        if ($requested_signatures === []) {
            return;
        }

        $product_id = (int) $this->product_id;

        $duplicate_exists = ProductVariant::query()
            ->where('product_id', $product_id)
            ->with('attributeValues:id,product_variant_id,attribute_id,language_id,value_string')
            ->get()
            ->contains(function (ProductVariant $variant) use ($requested_signatures): bool {
                $variant_signatures = $this->buildVariantAttributeSignaturesFromRows(
                    $variant->attributeValues
                        ->map(fn ($attribute_row): array => [
                            'attribute_id' => (int) data_get($attribute_row, 'attribute_id'),
                            'language_id'  => (int) data_get($attribute_row, 'language_id'),
                            'value_string' => (string) data_get($attribute_row, 'value_string'),
                        ])
                        ->all(),
                );

                foreach ($requested_signatures as $language_id => $requested_signature) {
                    if (($variant_signatures[$language_id] ?? null) === $requested_signature) {
                        return true;
                    }
                }

                return false;
            });

        if ($duplicate_exists) {
            throw ValidationException::withMessages([
                'data' => __('admin/catalogs/products/products.errors.duplicate_variant_attributes_combination'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $form_state
     * @return array<int, string>
     */
    private function buildVariantAttributeSignaturesByLanguage(array $form_state): array
    {
        $attribute_rows = collect($form_state)
            ->filter(fn (mixed $value, string $key): bool => str_starts_with($key, 'attribute_values_language_') && is_array($value))
            ->flatMap(function (array $language_rows, string $language_key): array {
                $language_id = (int) Str::of($language_key)->after('attribute_values_language_')->toString();

                if ($language_id < 1) {
                    return [];
                }

                return collect($language_rows)
                    ->map(fn (array $row): array => [
                        'attribute_id' => (int) ($row['attribute_id'] ?? 0),
                        'language_id'  => $language_id,
                        'value_string' => (string) ($row['value_string'] ?? ''),
                    ])
                    ->all();
            })
            ->all();

        return $this->buildVariantAttributeSignaturesFromRows($attribute_rows);
    }

    /**
     * @param  array<int, array{attribute_id:int,language_id:int,value_string:string}>  $rows
     * @return array<int, string>
     */
    private function buildVariantAttributeSignaturesFromRows(array $rows): array
    {
        return collect($rows)
            ->groupBy(fn (array $row): int => (int) $row['language_id'])
            ->map(function ($language_rows): ?string {
                $tokens = collect($language_rows)
                    ->map(function (array $row): ?string {
                        $attribute_id = (int) $row['attribute_id'];
                        $value_string = Str::of($row['value_string'])->trim()->lower()->toString();

                        if ($attribute_id < 1 || $value_string === '') {
                            return null;
                        }

                        return $attribute_id . ':' . $value_string;
                    })
                    ->filter(fn (?string $token): bool => $token !== null)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                if ($tokens === []) {
                    return null;
                }

                return hash('sha256', implode('|', $tokens));
            })
            ->filter(fn (?string $signature): bool => $signature !== null)
            ->all();
    }
}
