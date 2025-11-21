<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes\Pages;

use App\Filament\Resources\Catalogs\Attributes\Attributes\AttributeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttributes extends ListRecords
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
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
