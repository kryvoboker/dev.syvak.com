<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Slug;
use App\Services\Catalogs\Products\ProductCategorySyncService;
use Exception;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class CreateProduct extends CreateRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = ProductResource::class;

    protected array $descriptions = [];

    protected array $category_ids = [];

    protected array $slugs = [];

    protected array $images = [];

    public ?Model $record = null;

    /**
     * @throws Halt
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->descriptions = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->category_ids = app(ProductCategorySyncService::class)->normalizeCategoryIds($data['categories'] ?? []);
        $this->slugs = trim_strs_in_arr($data['slugs'] ?? []);
        $this->images = trim_strs_in_arr($data['images'] ?? []);
        $this->validateProductSlugsUniqueness();

        unset($data['descriptions'], $data['categories'], $data['slugs'], $data['images']);

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = DB::transaction(function () use ($data) {
            $this->record = static::getModel()::create($data);

            $this->createDescriptions();
            $this->syncImagesToDefaultVariant();

            if ($this->updateOrCreateSlugs() === false) {
                throw new Exception('Failed to create slugs');
            }

            return $this->record;
        });

        app(ProductCategorySyncService::class)->syncWithRetry(
            $this->getProductRecord(),
            $this->category_ids,
        );

        return $record;
    }

    protected function createDescriptions(): void
    {
        $descriptions_data = [];

        foreach ($this->descriptions as $language_id => $description) {
            if (! empty($description['name'])) {
                $descriptions_data[] = [
                    'language_id' => (int) $language_id,
                    'name' => $description['name'],
                    'description' => $description['description'] ?? null,
                    'meta_title' => $description['meta_title'] ?? null,
                    'meta_description' => $description['meta_description'] ?? null,
                    'meta_keywords' => $description['meta_keywords'] ?? null,
                ];
            }
        }

        if (! empty($descriptions_data)) {
            $this->getProductRecord()->productDescription()->createMany($descriptions_data);
        }
    }

    private function syncImagesToDefaultVariant(): void
    {
        $default_variant = $this->resolveDefaultVariant();

        $prepared_images = collect($this->images)
            ->filter(fn (mixed $image): bool => is_array($image))
            ->map(function (array $image_data): array {
                return [
                    'image' => (string) ($image_data['image'] ?? ''),
                    'sort_order' => max(0, (int) ($image_data['sort_order'] ?? 0)),
                ];
            })
            ->filter(fn (array $image_data): bool => filled($image_data['image']))
            ->values()
            ->all();

        if ($prepared_images === []) {
            return;
        }

        $default_variant->images()->createMany(
            collect($prepared_images)
                ->map(fn (array $image_data): array => [
                    'image' => $image_data['image'],
                    'sort_order' => $image_data['sort_order'],
                    'is_primary' => false,
                ])
                ->all(),
        );
    }

    private function resolveDefaultVariant(): ProductVariant
    {
        $product = $this->getProductRecord();

        if ($product->defaultVariant instanceof ProductVariant) {
            return $product->defaultVariant;
        }

        $default_variant = ProductVariant::query()->create([
            'product_id' => (int) $product->id,
            'is_default' => true,
            'is_active' => (bool) $product->is_active,
            'quantity' => (int) $product->quantity,
            'minimum' => max(1, (int) $product->minimum),
            'price' => (float) $product->price,
            'image' => $product->image,
            'date_available' => $product->date_available,
            'sort_order' => 0,
            'size_guide_data' => null,
            'composition_and_care_data' => null,
        ]);

        $product->default_variant_id = (int) $default_variant->id;
        $product->save();

        return $default_variant;
    }

    public function getTitle(): string
    {
        return __('admin/catalogs/products/products.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/catalogs/products/products.navigation_label');
    }

    private function getProductRecord(): Product
    {
        if (! $this->record instanceof Product) {
            throw new LogicException('Product record is not initialized.');
        }

        return $this->record;
    }

    /**
     * @throws ValidationException
     */
    private function validateProductSlugsUniqueness(): void
    {
        foreach ($this->slugs as $language_id => $slug_data) {
            $slug_value = Str::of((string) data_get($slug_data, 'name'))->trim()->toString();

            if ($slug_value === '') {
                continue;
            }

            $slug_exists = Slug::query()
                ->where('slug', $slug_value)
                ->whereIn('sluggable_type', [Product::class, ProductVariant::class])
                ->exists();

            if ($slug_exists) {
                throw ValidationException::withMessages([
                    "slugs.$language_id.name" => __('admin/catalogs/products/products.errors.duplicate_product_or_variant_slug_value'),
                ]);
            }
        }
    }
}
