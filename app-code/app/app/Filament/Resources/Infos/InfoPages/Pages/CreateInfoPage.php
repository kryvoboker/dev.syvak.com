<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Pages;

use App\Filament\Resources\Infos\InfoPages\InfoPageResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\Infos\InfoPage;
use Exception;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateInfoPage extends CreateRecord
{
    use ProcessSlugsTrait;

    protected static string    $resource     = InfoPageResource::class;
    protected array            $descriptions = [];
    protected array            $slugs        = [];
    public null|Model|InfoPage $record       = null;

    /**
     * @param array $data
     *
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store related data temporarily
        $this->descriptions = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->slugs        = trim_strs_in_arr($data['slugs'] ?? []);

        unset($data['descriptions'], $data['slugs']);

        return $data;
    }

    /**
     * Handle record creation with transaction.
     *
     * @param array $data
     *
     * @return Model
     * @throws Halt
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return DB::transaction(function () use ($data) {
                // Create main record
                $this->record = static::getModel()::create($data);

                // Process slugs
                if ($this->updateOrCreateSlugs() === false) {
                    throw new Exception('Failed to create slugs');
                }

                // Create descriptions
                $this->createDescriptions();

                return $this->record;
            });
        } catch (Exception|Throwable $e) {
            Log::channel('stack')->error('Failed to create InfoPage: ' . $e->getMessage(), [
                'data'      => $data,
                'exception' => $e,
            ]);

            $this->halt();
        }
    }

    /**
     * Create descriptions for the record.
     *
     * @return void
     */
    protected function createDescriptions(): void
    {
        $descriptions_data = [];

        foreach ($this->descriptions as $language_id => $description) {
            if (!empty($description['name'])) {
                $descriptions_data[] = [
                    'language_id'      => (int)$language_id,
                    'title'            => $description['name'],
                    'description'      => $description['description'] ?? null,
                    'meta_title'       => $description['meta_title'] ?? null,
                    'meta_description' => $description['meta_description'] ?? null,
                    'meta_keywords'    => $description['meta_keywords'] ?? null,
                ];
            }
        }

        if (!empty($descriptions_data)) {
            $this->record->infoPageDescription()->createMany($descriptions_data);
        }
    }
}
