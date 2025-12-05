<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Models\Catalogs\Products\Product;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string   $resource           = ProductResource::class;
    protected array           $descriptions       = [];
    protected array           $images             = [];
    protected array           $discounts          = [];
    protected array           $product_attributes = [];
    public null|Model|Product $record             = null;

    /**
     * Mutate form data before creating record
     *
     * @param array $data
     *
     * @return array
     * @throws Halt
     */
    protected function mutateFormDataBeforeCreate(array $data): array
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
     * Handle record creation after product is created
     *
     * @return void
     */
    protected function afterCreate(): void
    {
        // Create descriptions
        if (!empty($this->descriptions)) {
            $descriptions_data = [];

            foreach ($this->descriptions as $language_id => $description) {
                if (!empty($description['name'])) {
                    $descriptions_data[] = [
                        'language_id'      => (int)$language_id,
                        'name'             => $description['name'],
                        'description'      => $description['description'] ?? null,
                        'meta_title'       => $description['meta_title'] ?? null,
                        'meta_description' => $description['meta_description'] ?? null,
                        'meta_keywords'    => $description['meta_keywords'] ?? null,
                    ];
                }
            }

            if (!empty($descriptions_data)) {
                $this->record->productDescription()->createMany($descriptions_data);
            }
        }

        // Create images
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

        // Create discounts
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

        // Create attributes
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
