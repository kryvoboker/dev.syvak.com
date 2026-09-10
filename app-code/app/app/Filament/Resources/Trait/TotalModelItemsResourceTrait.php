<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trait;

use Illuminate\Database\Eloquent\Model;

trait TotalModelItemsResourceTrait
{
    /**
     * @return string|null
     */
    public static function getNavigationBadge(): ?string
    {
        $model = static::$model ?? null;

        if (! is_string($model) || ! is_a($model, Model::class, true)) {
            return '0';
        }

        $total_items_in_model = $model::query()->count();

        return $total_items_in_model > 0 ? (string) $total_items_in_model : '0';
    }
}
