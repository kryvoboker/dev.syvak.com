<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductDescription;
use App\Models\Catalogs\Products\ProductDiscount;
use App\Models\Catalogs\Products\ProductImage;
use App\Models\Catalogs\Products\ProductToAttribute;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class EditProduct extends EditRecord
{
    protected static string              $resource           = ProductResource::class;
    protected array                      $descriptions       = [];
    protected array                      $images             = [];
    protected array                      $discounts          = [];
    protected array                      $product_attributes = [];
    #[Locked]
    public Model|int|string|null|Product $record;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Mutate form data before filling form
     *
     * @param array $data
     *
     * @return array
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load descriptions
        $descriptions = $this->record->productDescription()
            ->get()
            ->keyBy('language_id')
            ->map(fn(ProductDescription $desc) => [
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
        $images = $this->record->productImage()
            ->orderBy('sort_order')
            ->get()
            ->map(fn(ProductImage $img) => [
                'id'         => $img->id,
                'image'      => $img->image,
                'sort_order' => $img->sort_order,
            ])
            ->toArray();

        $data['images'] = $images;

        // Load discounts
        $discounts = $this->record->productDiscount()
            ->get()
            ->map(fn(ProductDiscount $disc) => [
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
        $attributes = $this->record->productToAttribute()
            ->get()
            ->map(fn(ProductToAttribute $attr) => [
                'id'           => $attr->id,
                'attribute_id' => $attr->attribute_id,
                'language_id'  => $attr->language_id,
                'text'         => $attr->text,
            ])
            ->toArray();

        $data['attributes'] = $attributes;

        return $data;
    }

    /**
     * Mutate form data before saving
     *
     * @param array $data
     *
     * @return array
     * @throws Halt
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store related data temporarily
        $this->descriptions       = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->images             = $data['images'] ?? [];
        $this->discounts          = $data['discounts'] ?? [];
        $this->product_attributes = trim_strs_in_arr($data['attributes'] ?? []);

        // Validate unique attribute-language pairs
        $this->validateAttributeLanguagePairs($this->product_attributes);

        // Remove from main data
        unset($data['descriptions'], $data['images'], $data['discounts'], $data['attributes']);

        return $data;
    }

    /**
     * Validate that attribute-language pairs are unique
     *
     * @param array $attributes
     *
     * @return void
     * @throws Notification|Halt
     */
    protected function validateAttributeLanguagePairs(array $attributes): void
    {
        $pairs = [];

        foreach ($attributes as $index => $attribute) {
            if (empty($attribute['attribute_id']) || empty($attribute['language_id'])) {
                continue;
            }

            $pair = $attribute['attribute_id'] . '_' . $attribute['language_id'];

            if (in_array($pair, $pairs)) {
                Notification::make()
                    ->title(__('admin/catalogs/products/products.error_title'))
                    ->body(__('admin/catalogs/products/products.error_duplicate_attribute_language'))
                    ->danger()
                    ->send();

                $this->halt();
            }

            $pairs[] = $pair;
        }
    }

    /**
     * Handle after save
     *
     * @return void
     */
    protected function afterSave(): void
    {
        // Update descriptions
        if (!empty($this->descriptions)) {
            foreach ($this->descriptions as $language_id => $description) {
                if (!empty($description['name'])) {
                    $this->record->productDescription()->updateOrCreate(
                        ['language_id' => (int)$language_id],
                        [
                            'name'             => $description['name'],
                            'description'      => $description['description'] ?? null,
                            'meta_title'       => $description['meta_title'] ?? null,
                            'meta_description' => $description['meta_description'] ?? null,
                            'meta_keywords'    => $description['meta_keywords'] ?? null,
                        ]
                    );
                }
            }

            // Remove empty descriptions
            $filled_language_ids = collect($this->descriptions)
                ->filter(fn($desc) => !empty($desc['name']))
                ->keys()
                ->map(fn($id) => (int)$id)
                ->toArray();

            if (!empty($filled_language_ids)) {
                $this->record->productDescription()
                    ->whereNotIn('language_id', $filled_language_ids)
                    ->delete();
            }
        }

        // Update images
        $this->record->productImage()->delete();

        if (!empty($this->images)) {
            $images_data = [];

            foreach ($this->images as $image) {
                if (!empty($image['image'])) {
                    $images_data[] = [
                        'image'      => $image['image'],
                        'sort_order' => $image['sort_order'] ?? 0,
                    ];
                }
            }

            if (!empty($images_data)) {
                $this->record->productImage()->createMany($images_data);
            }
        }

        // Update discounts
        $this->record->productDiscount()->delete();

        if (!empty($this->discounts)) {
            $discounts_data = [];

            foreach ($this->discounts as $discount) {
                if (!empty($discount['price'])) {
                    $discounts_data[] = [
                        'customer_group_id' => $discount['customer_group_id'],
                        'quantity'          => $discount['quantity'],
                        'priority'          => $discount['priority'],
                        'price'             => $discount['price'],
                        'date_start'        => $discount['date_start'],
                        'date_end'          => $discount['date_end'],
                    ];
                }
            }

            if (!empty($discounts_data)) {
                $this->record->productDiscount()->createMany($discounts_data);
            }
        }

        // Update attributes
        $this->record->productToAttribute()->delete();

        if (!empty($this->product_attributes)) {
            $attributes_data = [];

            foreach ($this->product_attributes as $attribute) {
                if (!empty($attribute['attribute_id']) && !empty($attribute['text'])) {
                    $attributes_data[] = [
                        'attribute_id' => $attribute['attribute_id'],
                        'language_id'  => $attribute['language_id'],
                        'text'         => $attribute['text'],
                    ];
                }
            }

            if (!empty($attributes_data)) {
                $this->record->productToAttribute()->createMany($attributes_data);
            }
        }
    }

    /**
     * Get page title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return __('admin/catalogs/products/products.navigation_label');
    }

    /**
     * Get page heading
     *
     * @return string|null
     */
    public function getHeading(): ?string
    {
        return __('admin/catalogs/products/products.navigation_label');
    }
}
