<?php

declare(strict_types=1);

namespace App\Models\Trait;

trait SlugTrait
{
    /**
     * Get the route key name for the model
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
