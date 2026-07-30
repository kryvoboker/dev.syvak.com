<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Pages;

use App\Filament\Resources\Catalogs\Categories\Categories\CategoryResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Categories\CategoryDescription;
use App\Services\PageSettings\HeaderCategoryService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use LogicException;
use Throwable;

class EditCategory extends EditRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = CategoryResource::class;

    protected array $descriptions = [];

    protected array $slugs = [];

    protected ?string $preview_image        = null;
    protected ?int    $preview_image_width  = null;
    protected ?int    $preview_image_height = null;

    protected ?string $icon = null;

    protected bool $show_in_header = false;

    #[Locked]
    public int|string|Model|null $record = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn() => $this->save()),
            DeleteAction::make()
                ->icon(Heroicon::Trash),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record               = $this->getCategoryRecord();
        $category_image       = $record->categoryImage()->first();
        $preview_image        = $category_image->preview_image ?? null;
        $preview_image_width  = $category_image->preview_image_width ?? null;
        $preview_image_height = $category_image->preview_image_height ?? null;
        $icon                 = $category_image->icon ?? null;

        $data['preview_image']        = $preview_image;
        $data['preview_image_width']  = $preview_image_width;
        $data['preview_image_height'] = $preview_image_height;
        $data['icon']                 = $icon;

        // Load descriptions for each language
        $descriptions = $record->categoryDescription()
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

        $data['descriptions']   = $descriptions;
        $data['show_in_header'] = in_array(
            (int)$record->id,
            app(HeaderCategoryService::class)->getSelectedCategoryIds(),
            true,
        );

        $this->getSlugs($data);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->descriptions         = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->preview_image        = $data['preview_image'] ?? null;
        $this->preview_image_width  = isset($data['preview_image_width']) ? (int)$data['preview_image_width'] : null;
        $this->preview_image_height = isset($data['preview_image_height']) ? (int)$data['preview_image_height'] : null;
        $this->icon                 = $data['icon'] ?? null;
        $this->slugs                = trim_strs_in_arr($data['slugs'] ?? []);
        $this->show_in_header       = (bool)($data['show_in_header'] ?? false);

        unset($data['descriptions'], $data['preview_image'], $data['preview_image_width'], $data['preview_image_height'], $data['icon'], $data['slugs'], $data['show_in_header']);

        return $data;
    }

    /**
     * @param Model|Category $record
     * @param array          $data
     *
     * @return Model
     * @throws Throwable
     */
    protected function handleRecordUpdate(Model|Category $record, array $data): Model
    {
        if (!$record instanceof Category) {
            throw new LogicException('Category record has invalid type.');
        }

        return DB::transaction(function () use ($record, $data) {
            $record->update($data);

            $this->updateImage();

            if ($this->updateOrCreateSlugs() === false) {
                throw new Exception('Failed to update slugs');
            }

            $this->updateDescriptions();
            $record->rebuildPaths();
            $this->rebuildChildrenPaths($record->id);
            app(HeaderCategoryService::class)->setCategoryVisibility($record->id, $this->show_in_header);

            return $record;
        });
    }

    /**
     * Update image for the record
     */
    protected function updateImage(): void
    {
        $category_images = $this->getCategoryRecord()->categoryImage();

        if (!empty($this->preview_image) || !empty($this->icon)) {
            $category_images->updateOrCreate(
                [], // Empty array means "find the first related record"
                [
                    'icon'                 => $this->icon,
                    'preview_image'        => $this->preview_image,
                    'preview_image_width'  => $this->preview_image_width,
                    'preview_image_height' => $this->preview_image_height,
                ],
            );
        } else if ($category_images->exists()) {
            // If both images are empty, delete the record if it exists
            $category_images->delete();
        }
    }

    /**
     * Update descriptions for the record
     */
    protected function updateDescriptions(): void
    {
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
        if (!empty($language_ids_to_keep)) {
            $this->getCategoryRecord()->categoryDescription()
                ->whereNotIn('language_id', $language_ids_to_keep)
                ->delete();
        } else {
            $this->getCategoryRecord()->categoryDescription()->delete();
        }

        // Update or create descriptions
        foreach ($descriptions_to_sync as $language_id => $description_data) {
            $this->getCategoryRecord()->categoryDescription()->updateOrCreate(
                ['language_id' => (int)$language_id],
                $description_data,
            );
        }
    }

    /**
     * Rebuild paths for all children categories recursively
     */
    protected function rebuildChildrenPaths(int $parent_id): void
    {
        $children = (new Category())->getCategoryByParentId($parent_id);

        foreach ($children as $child) {
            $child->rebuildPaths();

            $this->rebuildChildrenPaths($child->id);
        }
    }

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/catalogs/categories/categories.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/catalogs/categories/categories.navigation_label');
    }

    private function getCategoryRecord(): Category
    {
        if (!$this->record instanceof Category) {
            throw new LogicException('Category record is not initialized.');
        }

        return $this->record;
    }
}
