<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Pages;

use App\Filament\Resources\Catalogs\Categories\Categories\CategoryResource;
use App\Models\Catalogs\Categories\Category;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCategory extends CreateRecord
{
    protected static string    $resource      = CategoryResource::class;
    protected array            $descriptions  = [];
    protected array            $slugs         = [];
    protected ?string          $preview_image = null;
    protected ?string          $icon          = null;
    public null|Model|Category $record        = null;

    /**
     * Mutate form data before creating record
     *
     * @param array $data
     *
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store descriptions temporarily
        $this->descriptions  = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->preview_image = $data['preview_image'] ?? null;
        $this->icon          = $data['icon'] ?? null;
        $this->slugs         = trim_strs_in_arr($data['slugs'] ?? []);

        unset($data['descriptions'], $data['preview_image'], $data['icon'], $data['slugs']);

        return $data;
    }

    /**
     * Handle record creation after attribute is created
     *
     * @return void
     */
    protected function afterCreate(): void
    {
        if (!empty($this->preview_image) || !empty($this->icon)) {
            $this->record->categoryImage()->create([
                'icon'          => $this->icon,
                'preview_image' => $this->preview_image,
            ]);
        }

        foreach ($this->slugs as $language_id => $slug_data) {
            if (empty($slug_data['name'])) {
                continue;
            }

            $this->record->slugs()->create([
                'language_id' => (int)$language_id,
                'slug'        => $slug_data['name'],
            ]);
        }

        $descriptions_data = [];

        foreach ($this->descriptions as $language_id => $description) {
            if (!empty($description['name'])) {
                $descriptions_data[] = [
                    'language_id'      => (int)$language_id,
                    'name'             => $description['name'],
                    'description'      => $description['description'],
                    'h1_title'         => $description['h1_title'],
                    'meta_title'       => $description['meta_title'],
                    'meta_description' => $description['meta_description'],
                    'meta_keywords'    => $description['meta_keywords'],
                ];
            }
        }

        if (!empty($descriptions_data)) {
            $this->record->categoryDescription()->createMany($descriptions_data);
        }

        // Rebuild category paths
        $this->record->rebuildPaths();
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
