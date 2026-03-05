<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\Catalogs\Products\Product;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class CreateProduct extends CreateRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = ProductResource::class;

    protected array $descriptions = [];

    protected array $images = [];

    protected array $discounts = [];

    protected array $product_attributes = [];

    protected array $slugs = [];

    public ?Model $record = null;

    /**
     * Mutate form data before creating record
     *
     *
     * @throws Halt
     */
    protected function mutateFormDataBeforeCreate(array $data): array
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
            $data['attributes'], $data['slugs'],
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

                // Stop further processing
                $this->halt();
            }

            $pairs[] = $pair;
        }
    }

    /**
     * Handle record creation with transaction
     *
     *
     * @throws Halt
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return DB::transaction(function () use ($data) {
                // Create main record
                $this->record = static::getModel()::create($data);

                // Create descriptions
                $this->createDescriptions();

                // Create images
                $this->createImages();

                // Create discounts
                $this->createDiscounts();

                // Create attributes
                $this->createAttributes();

                // Process slugs
                if ($this->updateOrCreateSlugs() === false) {
                    throw new Exception('Failed to create slugs');
                }

                return $this->record;
            });
        } catch (Exception|Throwable $e) {
            Log::channel('stack')->error('Failed to create Product: ' . $e->getMessage(), [
                'data'      => $data,
                'exception' => $e,
            ]);

            $this->halt();

            throw $e;
        }
    }

    /**
     * Create descriptions for the record
     */
    protected function createDescriptions(): void
    {
        $descriptions_data = [];

        foreach ($this->descriptions as $language_id => $description) {
            if (! empty($description['name'])) {
                $descriptions_data[] = [
                    'language_id'      => (int) $language_id,
                    'name'             => $description['name'],
                    'description'      => $description['description'] ?? null,
                    'meta_title'       => $description['meta_title'] ?? null,
                    'meta_description' => $description['meta_description'] ?? null,
                    'meta_keywords'    => $description['meta_keywords'] ?? null,
                ];
            }
        }

        if (! empty($descriptions_data)) {
            $this->getProductRecord()->productDescription()->createMany($descriptions_data);
        }
    }

    /**
     * Create images for the record
     */
    protected function createImages(): void
    {
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
     * Create discounts for the record
     */
    protected function createDiscounts(): void
    {
        $discounts_data = [];

        foreach ($this->discounts as $discount) {
            if (! empty($discount['price'])) {
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

        if (! empty($discounts_data)) {
            $this->getProductRecord()->productDiscount()->createMany($discounts_data);
        }
    }

    /**
     * Create attributes for the record
     */
    protected function createAttributes(): void
    {
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
