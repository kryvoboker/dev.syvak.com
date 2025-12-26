<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Currencies\Pages;

use App\Filament\Resources\Settings\Currencies\CurrencyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;

    /**
     * Get page title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return __('admin/settings/currencies.navigation_label');
    }

    /**
     * Get page heading
     *
     * @return string|null
     */
    public function getHeading(): ?string
    {
        return __('admin/settings/currencies.navigation_label');
    }
}
