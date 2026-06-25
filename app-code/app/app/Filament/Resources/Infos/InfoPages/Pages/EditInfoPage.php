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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class EditInfoPage extends EditRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = InfoPageResource::class;

    protected array $descriptions = [];

    protected array $slugs = [];

    public int|string|Model|null $record = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Collection<int, InfoPageDescription> $descriptions_collection */
        $descriptions_collection = $this->getInfoPageRecord()->infoPageDescription()->get();

        $descriptions = $descriptions_collection
            ->keyBy('language_id')
            ->map(fn (InfoPageDescription $descr): array => [
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
     *
     * @throws Halt
     * @throws Throwable
     */
    protected function handleRecordUpdate(Model|InfoPage $record, array $data): Model
    {
        if (! $record instanceof InfoPage) {
            throw new LogicException('Info page record has invalid type.');
        }

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

            throw $e;
        }
    }

    /**
     * Sync descriptions for the record.
     */
    protected function syncDescriptions(): void
    {
        foreach ($this->descriptions as $language_id => $description) {
            if (! empty($description['name'])) {
                $this->getInfoPageRecord()->infoPageDescription()->updateOrCreate(
                    ['language_id' => (int) $language_id],
                    [
                        'title'            => $description['name'],
                        'description'      => $description['description'] ?? null,
                        'meta_title'       => $description['meta_title'] ?? null,
                        'meta_description' => $description['meta_description'] ?? null,
                        'meta_keywords'    => $description['meta_keywords'] ?? null,
                    ],
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

    private function getInfoPageRecord(): InfoPage
    {
        if (! $this->record instanceof InfoPage) {
            throw new LogicException('Info page record is not initialized.');
        }

        return $this->record;
    }
}
