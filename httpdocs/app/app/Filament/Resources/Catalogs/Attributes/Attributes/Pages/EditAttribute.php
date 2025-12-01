<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes\Pages;

use App\Filament\Resources\Catalogs\Attributes\Attributes\AttributeResource;
use App\Models\Catalogs\Attributes\Attribute;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class EditAttribute extends EditRecord
{
    protected static string                $resource     = AttributeResource::class;
    protected array                        $descriptions = [];
    #[Locked]
    public Model|int|string|null|Attribute $record;

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
        // Load descriptions for each language
        $descriptions = $this->record->attributeDescription()
            ->get()
            ->keyBy('language_id')
            ->map(fn($desc) => [
                'language_id' => $desc->language_id,
                'name'        => $desc->name,
            ])
            ->toArray();

        $data['descriptions'] = $descriptions;

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
        $this->descriptions = trim_strs_in_arr($data['descriptions'] ?? []);

        unset($data['descriptions']);

        return $data;
    }

    /**
     * Handle after save
     *
     * @return void
     */
    protected function afterSave(): void
    {
        if (empty($this->descriptions)) {
            return;
        }

        foreach ($this->descriptions as $language_id => $description) {
            if (!empty($description['name'])) {
                $this->record->attributeDescription()->updateOrCreate(
                    ['language_id' => (int)$language_id],
                    ['name' => $description['name']]
                );
            }
        }

        // Remove descriptions that are empty
        $filled_language_ids = collect($this->descriptions)
            ->filter(fn($desc) => !empty($desc['name']))
            ->keys()
            ->map(fn($id) => (int)$id)
            ->toArray();

        if (!empty($filled_language_ids)) {
            $this->record->attributeDescription()
                ->whereNotIn('language_id', $filled_language_ids)
                ->delete();
        }
    }

    /**
     * Get page title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return __('admin/catalogs/attributes/attributes.navigation_label');
    }

    /**
     * Get page heading
     *
     * @return string|null
     */
    public function getHeading(): ?string
    {
        return __('admin/catalogs/attributes/attributes.navigation_label');
    }
}
