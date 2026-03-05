<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Pages;

use App\Filament\Resources\Catalogs\Categories\Categories\CategoryResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\Catalogs\Categories\Category;
use Exception;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class CreateCategory extends CreateRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = CategoryResource::class;

    protected array $descriptions = [];

    protected array $slugs = [];

    protected ?string $preview_image = null;

    protected ?string $icon = null;

    public ?Model $record = null;

    /**
     * Mutate form data before creating record
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store related data temporarily
        $this->descriptions  = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->preview_image = $data['preview_image'] ?? null;
        $this->icon          = $data['icon'] ?? null;
        $this->slugs         = trim_strs_in_arr($data['slugs'] ?? []);

        unset($data['descriptions'], $data['preview_image'], $data['icon'], $data['slugs']);

        return $data;
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

                // Create image
                $this->createImage();

                // Process slugs
                if ($this->updateOrCreateSlugs() === false) {
                    throw new Exception('Failed to create slugs');
                }

                // Create descriptions
                $this->createDescriptions();

                // Rebuild category paths
                $this->getCategoryRecord()->rebuildPaths();

                return $this->record;
            });
        } catch (Exception|Throwable $e) {
            Log::channel('stack')->error('Failed to create Category: ' . $e->getMessage(), [
                'data'      => $data,
                'exception' => $e,
            ]);

            $this->halt();

            throw $e;
        }
    }

    /**
     * Create image for the record
     */
    protected function createImage(): void
    {
        if (! empty($this->preview_image) || ! empty($this->icon)) {
            $this->getCategoryRecord()->categoryImage()->create([
                'icon'          => $this->icon,
                'preview_image' => $this->preview_image,
            ]);
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
                    'h1_title'         => $description['h1_title'] ?? null,
                    'meta_title'       => $description['meta_title'] ?? null,
                    'meta_description' => $description['meta_description'] ?? null,
                    'meta_keywords'    => $description['meta_keywords'] ?? null,
                ];
            }
        }

        if (! empty($descriptions_data)) {
            $this->getCategoryRecord()->categoryDescription()->createMany($descriptions_data);
        }
    }

    private function getCategoryRecord(): Category
    {
        if (! $this->record instanceof Category) {
            throw new LogicException('Category record is not initialized.');
        }

        return $this->record;
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
}
