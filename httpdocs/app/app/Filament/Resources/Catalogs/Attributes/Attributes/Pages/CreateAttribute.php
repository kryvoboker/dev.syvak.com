<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes\Pages;

use App\Filament\Resources\Catalogs\Attributes\Attributes\AttributeResource;
use App\Models\Catalogs\Attributes\Attribute;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAttribute extends CreateRecord
{
    protected static string     $resource     = AttributeResource::class;
    protected array             $descriptions = [];
    public null|Model|Attribute $record       = null;

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
        $this->descriptions = trim_strs_in_arr($data['descriptions'] ?? []);

        unset($data['descriptions']);

        return $data;
    }

    /**
     * Handle record creation after attribute is created
     *
     * @return void
     */
    protected function afterCreate(): void
    {
        if (!empty($this->descriptions)) {
            $descriptions_data = [];

            foreach ($this->descriptions as $language_id => $description) {
                if (!empty($description['name'])) {
                    $descriptions_data[] = [
                        'language_id' => (int)$language_id,
                        'name'        => $description['name'],
                    ];
                }
            }

            if (!empty($descriptions_data)) {
                $this->record->attributeDescription()->createMany($descriptions_data);
            }
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
