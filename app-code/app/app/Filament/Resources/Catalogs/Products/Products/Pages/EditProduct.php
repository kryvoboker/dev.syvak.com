<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Catalogs\Products\Products\ProductVariantResource;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Filament\Resources\Trait\StorefrontProductLinkTrait;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductDescription;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Slug;
use App\Services\Catalogs\Products\ProductCategorySyncService;
use App\Services\Catalogs\Products\ProductVariantContentPersistenceService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use LogicException;

class EditProduct extends EditRecord
{
    use ProcessSlugsTrait;
    use LanguageTrait;
    use StorefrontProductLinkTrait;

    protected static string $resource = ProductResource::class;

    protected array $descriptions = [];

    protected array $category_ids = [];

    protected array $slugs = [];

    protected array $images = [];

    protected array $variant_relationship_data = [];

    #[Locked]
    public int|string|Model|null $record = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_storefront')
                ->label('')
                ->icon(Heroicon::Eye)
                ->tooltip(__('actions.view'))
                ->url(fn (): ?string => self::getStorefrontProductUrl($this->getProductRecord(), self::getCurrentLanguageId()))
                ->visible(fn (): bool => self::getStorefrontProductUrl($this->getProductRecord(), self::getCurrentLanguageId()) !== null)
                ->openUrlInNewTab(),
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
            Action::make('manage_variants')
                ->label(__('admin/catalogs/products/products.actions.manage_variants'))
                ->icon(Heroicon::RectangleStack)
                ->url(fn (): string => ProductVariantResource::getUrl('index', [
                    'product' => (int)data_get($this->getProductRecord(), 'id'),
                ])),
            DeleteAction::make()
                ->icon(Heroicon::Trash),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getProductRecord();

        $descriptions = $record->productDescription()
            ->get()
            ->keyBy('language_id')
            ->map(fn (ProductDescription $desc) => [
                'language_id' => $desc->language_id,
                'name' => $desc->name,
                'description' => $desc->description,
                'meta_title' => $desc->meta_title,
                'meta_description' => $desc->meta_description,
                'meta_keywords' => $desc->meta_keywords,
            ])
            ->toArray();

        $data['descriptions'] = $descriptions;
        $data['categories'] = $record->categories()
            ->pluck('categories.id')
            ->map(fn (mixed $category_id): int => (int)$category_id)
            ->all();
        $data['images'] = $record->defaultVariant?->images()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($image): array => [
                'image' => $image->image,
                'sort_order' => (int)$image->sort_order,
            ])
            ->values()
            ->all() ?? [];

        $data = array_merge(
            $data,
            app(ProductVariantContentPersistenceService::class)->hydrateFormData($record),
        );

        $this->getSlugs($data);

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->descriptions = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->category_ids = app(ProductCategorySyncService::class)->normalizeCategoryIds($data['categories'] ?? []);
        $this->slugs = trim_strs_in_arr($data['slugs'] ?? []);
        $this->images = trim_strs_in_arr($data['images'] ?? []);
        $prepared_variant_data = app(ProductVariantContentPersistenceService::class)->prepareForSave($data);
        $this->variant_relationship_data = $prepared_variant_data['relationships'];
        $this->validateProductSlugsUniqueness();

        $data = $prepared_variant_data['attributes'];
        unset($data['descriptions'], $data['categories'], $data['slugs'], $data['images']);

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function handleRecordUpdate(Model|Product $record, array $data): Model
    {
        if (!$record instanceof Product) {
            throw new LogicException('Product record has invalid type.');
        }

        DB::transaction(function () use ($record, $data): void {
            $record->update($data);

            $this->updateDescriptions();
            $this->syncImagesToDefaultVariant();
            app(ProductVariantContentPersistenceService::class)->syncRelations(
                $this->getProductRecord(),
                $this->variant_relationship_data,
            );

            if ($this->updateOrCreateSlugs() === false) {
                throw new Exception('Failed to update slugs');
            }
        });

        app(ProductCategorySyncService::class)->syncWithRetry(
            $record,
            $this->category_ids,
        );

        return $record;
    }

    protected function updateDescriptions(): void
    {
        foreach ($this->descriptions as $language_id => $description) {
            if (!empty($description['name'])) {
                $this->getProductRecord()->productDescription()->updateOrCreate(
                    ['language_id' => (int)$language_id],
                    [
                        'name' => $description['name'],
                        'description' => $description['description'] ?? null,
                        'meta_title' => $description['meta_title'] ?? null,
                        'meta_description' => $description['meta_description'] ?? null,
                        'meta_keywords' => $description['meta_keywords'] ?? null,
                    ],
                );
            }
        }

        $filled_language_ids = collect($this->descriptions)
            ->filter(fn ($desc) => !empty($desc['name']))
            ->keys()
            ->map(fn ($id) => (int)$id)
            ->toArray();

        if (!empty($filled_language_ids)) {
            $this->getProductRecord()->productDescription()
                ->whereNotIn('language_id', $filled_language_ids)
                ->delete();
        }
    }

    private function syncImagesToDefaultVariant(): void
    {
        $default_variant = $this->resolveDefaultVariant();

        $prepared_images = collect($this->images)
            ->filter(fn (mixed $image): bool => is_array($image))
            ->map(function (array $image_data): array {
                return [
                    'image' => (string)($image_data['image'] ?? ''),
                    'sort_order' => max(0, (int)($image_data['sort_order'] ?? 0)),
                ];
            })
            ->filter(fn (array $image_data): bool => filled($image_data['image']))
            ->values()
            ->all();

        $default_variant->images()->delete();

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
            'product_id' => (int)$product->id,
            'is_default' => true,
            'is_active' => (bool)$product->is_active,
            'quantity' => (int)$product->quantity,
            'minimum' => max(1, (int)$product->minimum),
            'price' => (float)$product->price,
            'image' => $product->image,
            'date_available' => $product->date_available,
            'sort_order' => 1,
        ]);

        $product->default_variant_id = (int)$default_variant->id;
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
        if (!$this->record instanceof Product) {
            throw new LogicException('Product record is not initialized.');
        }

        return $this->record;
    }

    /**
     * @throws ValidationException
     */
    private function validateProductSlugsUniqueness(): void
    {
        $current_product_id = (int)data_get($this->getProductRecord(), 'id');

        foreach ($this->slugs as $language_id => $slug_data) {
            $slug_value = Str::of((string)data_get($slug_data, 'name'))->trim()->toString();

            if ($slug_value === '') {
                continue;
            }

            $slug_exists = Slug::query()
                ->where('slug', $slug_value)
                ->whereIn('sluggable_type', [Product::class, ProductVariant::class])
                ->whereNot(function ($query) use ($current_product_id): void {
                    $query
                        ->where('sluggable_type', Product::class)
                        ->where('sluggable_id', $current_product_id);
                })
                ->exists();

            if ($slug_exists) {
                throw ValidationException::withMessages([
                    "slugs.$language_id.name" => __('admin/catalogs/products/products.errors.duplicate_product_or_variant_slug_value'),
                ]);
            }
        }
    }
}
