<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Pages;

use App\Filament\Resources\Infos\InfoPages\InfoPageResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\Infos\InfoPage;
use App\Models\Infos\InfoPageDescription;
use Exception;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EditInfoPage extends EditRecord
{
    use ProcessSlugsTrait;

    protected static string               $resource     = InfoPageResource::class;
    protected array                       $descriptions = [];
    protected array                       $slugs        = [];
    public string|int|null|Model|InfoPage $record       = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $descriptions = $this->record->infoPageDescription()
            ->get()
            ->keyBy('language_id')
            ->map(fn(InfoPageDescription $descr) => [
                'name'             => $descr->title,
                'description'      => $descr->description,
                'meta_title'       => $descr->meta_title,
                'meta_description' => $descr->meta_description,
                'meta_keywords'    => $descr->meta_keywords,
            ])
            ->toArray();

        $data['descriptions'] = $descriptions;

        $this->getSlugs($data);

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store related data temporarily
        $this->descriptions = trim_strs_in_arr($data['descriptions'] ?? []);
        $this->slugs        = trim_strs_in_arr($data['slugs'] ?? []);

        unset($data['descriptions'], $data['slugs']);

        return $data;
    }

    /**
     * Handle record update with transaction.
     *
     * @param Model|InfoPage $record
     * @param array          $data
     *
     * @return Model
     * @throws Halt
     * @throws Throwable
     */
    protected function handleRecordUpdate(Model|InfoPage $record, array $data): Model
    {
        try {
            return DB::transaction(function () use ($record, $data) {
                // Update main record
                $record->update($data);

                // Process slugs
                if ($this->updateOrCreateSlugs() === false) {
                    throw new Exception('Failed to update slugs');
                }

                // Sync descriptions
                $this->syncDescriptions();

                return $record;
            });
        } catch (Exception $e) {
            Log::channel('stack')->error('Failed to update InfoPage: ' . $e->getMessage(), [
                'record_id' => $record->id,
                'data'      => $data,
                'exception' => $e,
            ]);

            $this->halt();
        }
    }

    /**
     * Sync descriptions for the record.
     *
     * @return void
     */
    protected function syncDescriptions(): void
    {
        foreach ($this->descriptions as $language_id => $description) {
            if (!empty($description['name'])) {
                $this->record->infoPageDescription()->updateOrCreate(
                    ['language_id' => (int)$language_id],
                    [
                        'title'            => $description['name'],
                        'description'      => $description['description'] ?? null,
                        'meta_title'       => $description['meta_title'] ?? null,
                        'meta_description' => $description['meta_description'] ?? null,
                        'meta_keywords'    => $description['meta_keywords'] ?? null,
                    ]
                );
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
