<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Catalogs\Products\Products\ProductVariantResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductDescription;
use App\Services\Catalogs\Products\ProductCategorySyncService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use LogicException;

class EditProduct extends EditRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = ProductResource::class;

    protected array $descriptions = [];

    protected array $category_ids = [];

    protected array $slugs = [];

    #[Locked]
    public int|string|Model|null $record = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_variants')
                ->label(__('admin/catalogs/products/products.actions.manage_variants'))
                ->icon(Heroicon::RectangleStack)
                ->url(fn (): string => ProductVariantResource::getUrl('index', [
                    'product' => (int) data_get($this->getProductRecord(), 'id'),
                ])),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getProductRecord();

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
        $data['categories']   = $record->categories()
            ->pluck('categories.id')
            ->map(fn (mixed $category_id): int => (int) $category_id)
            ->all();

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
        $this->slugs        = trim_strs_in_arr($data['slugs'] ?? []);

        unset($data['descriptions'], $data['categories'], $data['slugs']);

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function handleRecordUpdate(Model|Product $record, array $data): Model
    {
        if (! $record instanceof Product) {
            throw new LogicException('Product record has invalid type.');
        }

        DB::transaction(function () use ($record, $data): void {
            $record->update($data);

            $this->updateDescriptions();

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
