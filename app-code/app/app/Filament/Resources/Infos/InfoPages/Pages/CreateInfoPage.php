<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Pages;

use App\Filament\Resources\Infos\InfoPages\InfoPageResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\Infos\InfoPage;
use Exception;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class CreateInfoPage extends CreateRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = InfoPageResource::class;

    protected array $descriptions = [];

    protected array $slugs = [];

    public ?Model $record = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store related data temporarily
        $this->descriptions = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->slugs = trim_strs_in_arr($data['slugs'] ?? []);

        unset($data['descriptions'], $data['slugs']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $this->record = static::getModel()::create($data);

            if ($this->updateOrCreateSlugs() === false) {
                throw new Exception('Failed to create slugs');
            }

            $this->createDescriptions();

            return $this->record;
        });
    }

    /**
     * Create descriptions for the record.
     */
    protected function createDescriptions(): void
    {
        $descriptions_data = [];

        foreach ($this->descriptions as $language_id => $description) {
            if (! empty($description['name'])) {
                $descriptions_data[] = [
                    'language_id' => (int) $language_id,
                    'title' => $description['name'],
                    'description' => $description['description'] ?? null,
                    'meta_title' => $description['meta_title'] ?? null,
                    'meta_description' => $description['meta_description'] ?? null,
                    'meta_keywords' => $description['meta_keywords'] ?? null,
                ];
            }
        }

        if (! empty($descriptions_data)) {
            $this->getInfoPageRecord()->infoPageDescription()->createMany($descriptions_data);
        }
    }

    private function getInfoPageRecord(): InfoPage
    {
        if (! $this->record instanceof InfoPage) {
            throw new LogicException('Info page record is not initialized.');
        }

        return $this->record;
    }
}
