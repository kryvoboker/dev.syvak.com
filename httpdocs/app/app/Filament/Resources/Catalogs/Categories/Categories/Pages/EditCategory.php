<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Pages;

use App\Filament\Resources\Catalogs\Categories\Categories\CategoryResource;
use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Categories\CategoryDescription;
use App\Models\Slug;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class EditCategory extends EditRecord
{
    protected static string               $resource      = CategoryResource::class;
    protected array                       $descriptions  = [];
    protected array                       $slugs         = [];
    protected ?string                     $preview_image = null;
    protected ?string                     $icon          = null;
    #[Locked]
    public int|string|Model|Category|null $record        = null;

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
        $category_image = $this->record->categoryImage()->first();
        $preview_image  = $category_image?->preview_image ?? null;
        $icon           = $category_image?->icon ?? null;

        $data['preview_image'] = $preview_image;
        $data['icon']          = $icon;

        // Load descriptions for each language
        $descriptions = $this->record->categoryDescription()
            ->get()
            ->keyBy('language_id')
            ->map(fn(CategoryDescription $desc) => [
                'language_id'      => $desc->language_id,
                'name'             => $desc->name,
                'description'      => $desc->description,
                'h1_title'         => $desc->h1_title,
                'meta_title'       => $desc->meta_title,
                'meta_description' => $desc->meta_description,
                'meta_keywords'    => $desc->meta_keywords,
            ])
            ->toArray();

        $data['descriptions'] = $descriptions;

        $slugs = $this->record->slugs()
            ->get()
            ->keyBy('language_id')
            ->map(fn(Slug $slug): array => [
                'language_id' => $slug->language_id,
                'name'        => $slug->slug,
            ])
            ->toArray();

        $data['slugs'] = $slugs;

        return $data;
    }

    /**
     * Mutate form data before saving
     *
     * @param array $data
     *
     * @return array
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->descriptions  = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->preview_image = $data['preview_image'] ?? null;
        $this->icon          = $data['icon'] ?? null;
        $this->slugs         = trim_strs_in_arr($data['slugs'] ?? []);

        unset($data['descriptions'], $data['preview_image'], $data['icon'], $data['slugs']);

        return $data;
    }

    /**
     * Handle after save
     *
     * @return void
     */
    protected function afterSave(): void
    {
        $category_images = $this->record->categoryImage();

        if (!empty($this->preview_image) || !empty($this->icon)) {
            $category_images->updateOrCreate(
                [], // Empty array means "find the first related record"
                [
                    'icon'          => $this->icon,
                    'preview_image' => $this->preview_image,
                ]);
        } else if ($category_images->exists()) {
            // If both images are empty, delete the record if it exists
            $category_images->delete();
        }

        foreach ($this->slugs as $language_id => $slug_data) {
            if (empty($slug_data['name'])) {
                continue;
            }

            $this->record->slugs()->updateOrCreate(
                ['language_id' => (int)$language_id],
                ['slug' => $slug_data['name']]
            );
        }

        // Collect language IDs with non-empty names
        $language_ids_to_keep = [];
        $descriptions_to_sync = [];

        foreach ($this->descriptions as $language_id => $description) {
            if (!empty($description['name'])) {
                $language_ids_to_keep[] = (int)$language_id;

                $descriptions_to_sync[(int)$language_id] = [
                    'name'             => $description['name'],
                    'description'      => $description['description'] ?? null,
                    'h1_title'         => $description['h1_title'] ?? null,
                    'meta_title'       => $description['meta_title'] ?? null,
                    'meta_description' => $description['meta_description'] ?? null,
                    'meta_keywords'    => $description['meta_keywords'] ?? null,
                ];
            }
        }

        // Delete descriptions for languages that are not in the list or have empty names
        $this->record->categoryDescription()
            ->whereNotIn('language_id', $language_ids_to_keep)
            ->delete();

        // Update or create descriptions
        foreach ($descriptions_to_sync as $language_id => $description_data) {
            $this->record->categoryDescription()->updateOrCreate(
                ['language_id' => (int)$language_id],
                $description_data
            );
        }

        // Rebuild category paths if parent changed
        $this->record->rebuildPaths();

        // Rebuild paths for all children
        $this->rebuildChildrenPaths($this->record->id);
    }

    /**
     * Rebuild paths for all children categories recursively
     *
     * @param int $parent_id
     *
     * @return void
     */
    protected function rebuildChildrenPaths(int $parent_id): void
    {
        $children = new Category()->getCategoryByParentId($parent_id);

        foreach ($children as $child) {
            $child->rebuildPaths();

            $this->rebuildChildrenPaths($child->id);
        }
    }

    /**
     * Get page title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return __('admin/catalogs/categories/categories.navigation_label');
    }

    /**
     * Get page heading
     *
     * @return string|null
     */
    public function getHeading(): ?string
    {
        return __('admin/catalogs/categories/categories.navigation_label');
    }
}
