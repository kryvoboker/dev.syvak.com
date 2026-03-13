<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductDescription;
use App\Models\Catalogs\Products\ProductDiscount;
use App\Models\Catalogs\Products\ProductImage;
use App\Models\Catalogs\Products\ProductToAttribute;
use App\Services\Catalogs\Products\ProductCategorySyncService;
use Exception;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use LogicException;
use Throwable;

class EditProduct extends EditRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = ProductResource::class;

    protected array $descriptions = [];

    protected array $images = [];

    protected array $discounts = [];

    protected array $product_attributes = [];

    protected array $slugs = [];

    #[Locked]
    public int|string|Model|null $record = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Mutate form data before filling form
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getProductRecord();

        // Load descriptions
        $descriptions = $record->productDescription()
            ->get()
            ->keyBy('language_id')
            ->map(fn (ProductDescription $desc) => [
                'language_id'      => $desc->language_id,
                'name'             => $desc->name,
                'description'      => $desc->description,
                'meta_title'       => $desc->meta_title,
                'meta_description' => $desc->meta_description,
                'meta_keywords'    => $desc->meta_keywords,
            ])
            ->toArray();

        $data['descriptions'] = $descriptions;

        // Load images
        $images = $record->productImage()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ProductImage $img) => [
                'id'         => $img->id,
                'image'      => $img->image,
                'sort_order' => $img->sort_order,
            ])
            ->toArray();

        $data['images'] = $images;

        // Load discounts
        $discounts = $record->productDiscount()
            ->get()
            ->map(fn (ProductDiscount $disc) => [
                'id'            => $disc->id,
                'user_group_id' => $disc->user_group_id,
                'quantity'      => $disc->quantity,
                'priority'      => $disc->priority,
                'price'         => $disc->price,
                'date_start'    => $disc->date_start,
                'date_end'      => $disc->date_end,
            ])
            ->toArray();

        $data['discounts'] = $discounts;

        // Load attributes
        $attributes = $record->productToAttribute()
            ->get()
            ->map(fn (ProductToAttribute $attr) => [
                'id'           => $attr->id,
                'attribute_id' => $attr->attribute_id,
                'language_id'  => $attr->language_id,
                'text'         => $attr->text,
            ])
            ->toArray();

        $data['attributes'] = $attributes;
        $data['categories'] = $record->categories()
            ->pluck('categories.id')
            ->map(fn (mixed $category_id): int => (int) $category_id)
            ->all();

        $this->getSlugs($data);

        return $data;
    }

    /**
     * Mutate form data before saving
     *
     *
     * @throws Halt
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store related data temporarily
        $this->descriptions       = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->images             = $data['images'] ?? [];
        $this->discounts          = $data['discounts'] ?? [];
        $this->product_attributes = trim_strs_in_arr($data['attributes'] ?? []);
        $this->slugs              = trim_strs_in_arr($data['slugs'] ?? []);

        // Validate unique attribute-language pairs
        $this->validateAttributeLanguagePairs($this->product_attributes);

        // Remove from main data
        unset(
            $data['descriptions'], $data['images'], $data['discounts'],
            $data['attributes'], $data['categories'], $data['slugs'],
        );

        return $data;
    }

    /**
     * Validate that attribute-language pairs are unique
     *
     *
     * @throws Halt
     */
    protected function validateAttributeLanguagePairs(array $attributes): void
    {
        $pairs = [];

        foreach ($attributes as $attribute) {
            if (empty($attribute['attribute_id']) || empty($attribute['language_id'])) {
                continue;
            }

            $pair = $attribute['attribute_id'] . '_' . $attribute['language_id'];

            if (in_array($pair, $pairs)) {
                Notification::make()
                    ->title(__('admin/default.errors.title'))
                    ->body(__('admin/catalogs/products/products.errors.duplicate_attribute_language'))
                    ->danger()
                    ->send();

                $this->halt();
            }

            $pairs[] = $pair;
        }
    }

    /**
     * Handle record update with transaction
     *
     *
     * @throws Halt
     */
    protected function handleRecordUpdate(Model|Product $record, array $data): Model
    {
        if (! $record instanceof Product) {
            throw new LogicException('Product record has invalid type.');
        }

        try {
            DB::transaction(function () use ($record, $data): void {
                // Update main record
                $record->update($data);

                // Update descriptions
                $this->updateDescriptions();

                // Update images
                $this->updateImages();

                // Update discounts
                $this->updateDiscounts();

                // Update attributes
                $this->updateAttributes();

                // Process slugs
                if ($this->updateOrCreateSlugs() === false) {
                    throw new Exception('Failed to update slugs');
                }
            });

            app(ProductCategorySyncService::class)->syncWithRetry(
                $record,
                $data['categories'] ?? [],
            );

            return $record;
        } catch (Throwable $e) {
            Log::channel('stack')->error('Failed to update Product: ' . $e->getMessage(), [
                'record_id' => $record->id,
                'data'      => $data,
                'exception' => $e,
            ]);

            $this->halt();

            throw $e;
        }
    }

    /**
     * Update descriptions for the record
     */
    protected function updateDescriptions(): void
    {
        foreach ($this->descriptions as $language_id => $description) {
            if (! empty($description['name'])) {
                $this->getProductRecord()->productDescription()->updateOrCreate(
                    ['language_id' => (int) $language_id],
                    [
                        'name'             => $description['name'],
                        'description'      => $description['description'] ?? null,
                        'meta_title'       => $description['meta_title'] ?? null,
                        'meta_description' => $description['meta_description'] ?? null,
                        'meta_keywords'    => $description['meta_keywords'] ?? null,
                    ],
                );
            }
        }

        // Remove empty descriptions
        $filled_language_ids = collect($this->descriptions)
            ->filter(fn ($desc) => ! empty($desc['name']))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (! empty($filled_language_ids)) {
            $this->getProductRecord()->productDescription()
                ->whereNotIn('language_id', $filled_language_ids)
                ->delete();
        }
    }

    /**
     * Update images for the record
     */
    protected function updateImages(): void
    {
        $this->getProductRecord()->productImage()->delete();

        $images_data = [];

        foreach ($this->images as $image) {
            if (! empty($image['image'])) {
                $images_data[] = [
                    'image'      => $image['image'],
                    'sort_order' => $image['sort_order'] ?? 0,
                ];
            }
        }

        if (! empty($images_data)) {
            $this->getProductRecord()->productImage()->createMany($images_data);
        }
    }

    /**
     * Update discounts for the record
     */
    protected function updateDiscounts(): void
    {
        $this->getProductRecord()->productDiscount()->delete();

        $discounts_data = [];

        foreach ($this->discounts as $discount) {
            if (! empty($discount['price'])) {
                $discounts_data[] = [
                    'user_group_id' => $discount['user_group_id'],
                    'quantity'      => $discount['quantity'],
                    'priority'      => $discount['priority'],
                    'price'         => $discount['price'],
                    'date_start'    => $discount['date_start'],
                    'date_end'      => $discount['date_end'],
                ];
            }
        }

        if (! empty($discounts_data)) {
            $this->getProductRecord()->productDiscount()->createMany($discounts_data);
        }
    }

    /**
     * Update attributes for the record
     */
    protected function updateAttributes(): void
    {
        $this->getProductRecord()->productToAttribute()->delete();

        $attributes_data = [];

        foreach ($this->product_attributes as $attribute) {
            if (! empty($attribute['attribute_id']) && ! empty($attribute['text'])) {
                $attributes_data[] = [
                    'attribute_id' => $attribute['attribute_id'],
                    'language_id'  => $attribute['language_id'],
                    'text'         => $attribute['text'],
                ];
            }
        }

        if (! empty($attributes_data)) {
            $this->getProductRecord()->productToAttribute()->createMany($attributes_data);
        }
    }

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/catalogs/products/products.navigation_label');
    }

    /**
     * Get page heading
     */
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
}
