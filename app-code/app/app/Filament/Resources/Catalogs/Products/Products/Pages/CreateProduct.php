<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\Catalogs\Products\Product;
use App\Services\Catalogs\Products\ProductCategorySyncService;
use Exception;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class CreateProduct extends CreateRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = ProductResource::class;

    protected array $descriptions = [];

    protected array $category_ids = [];

    protected array $slugs = [];

    public ?Model $record = null;

    /**
     * @throws Halt
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->descriptions = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->category_ids = app(ProductCategorySyncService::class)->normalizeCategoryIds($data['categories'] ?? []);
        $this->slugs        = trim_strs_in_arr($data['slugs'] ?? []);

        unset($data['descriptions'], $data['categories'], $data['slugs']);

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
}
