<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes\Pages;

use App\Filament\Resources\Catalogs\Attributes\Attributes\AttributeResource;
use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Catalogs\Attributes\AttributeDescription;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use LogicException;

class EditAttribute extends EditRecord
{
    protected static string $resource = AttributeResource::class;

    /** @var array<int|string, array<string, mixed>> */
    protected array $descriptions = [];

    #[Locked]
    public int|string|Model|null $record = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
            DeleteAction::make()
                ->icon(Heroicon::Trash),
        ];
    }

    /**
     * Mutate form data before filling form
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load descriptions for each language
        $descriptions = $this->getAttributeRecord()->attributeDescription()
            ->get()
            ->keyBy('language_id')
            ->map(fn (AttributeDescription $desc) => [
                'language_id' => $desc->language_id,
                'name' => $desc->name,
            ])
            ->toArray();

        $data['descriptions'] = $descriptions;

        return $data;
    }

    /**
     * Mutate form data before saving
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->descriptions = trim_strs_in_arr((array) ($data['descriptions'] ?? []));

        unset($data['descriptions']);

        return $data;
    }

    /**
     * Handle after save
     */
    protected function afterSave(): void
    {
        if (empty($this->descriptions)) {
            return;
        }

        foreach ($this->descriptions as $language_id => $description) {
            if (! empty($description['name'])) {
                $this->getAttributeRecord()->attributeDescription()->updateOrCreate(
                    ['language_id' => (int) $language_id],
                    ['name' => $description['name']],
                );
            }
        }

        // Remove descriptions that are empty
        $filled_language_ids = collect($this->descriptions)
            ->filter(fn ($desc) => ! empty($desc['name']))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (! empty($filled_language_ids)) {
            $this->getAttributeRecord()->attributeDescription()
                ->whereNotIn('language_id', $filled_language_ids)
                ->delete();
        }
    }

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/catalogs/attributes/attributes.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/catalogs/attributes/attributes.navigation_label');
    }

    private function getAttributeRecord(): Attribute
    {
        if (! $this->record instanceof Attribute) {
            throw new LogicException('Attribute record is not initialized.');
        }

        return $this->record;
    }
}
