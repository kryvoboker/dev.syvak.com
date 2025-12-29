<?php

declare(strict_types=1);

namespace App\Models\Infos;

use App\Models\Trait\HasSlugsTrait;
use App\Models\Trait\SlugTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfoPage extends Model
{
    use SlugTrait, HasSlugsTrait;

    protected $fillable = [
        'position',
        'sort_order',
        'is_active',
        'is_noindex',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
            'is_noindex' => 'boolean',
        ];
    }

    /**
     * @return HasMany<InfoPageDescription>
     */
    public function infoPageDescription(): HasMany
    {
        return $this->hasMany(InfoPageDescription::class);
    }
}
