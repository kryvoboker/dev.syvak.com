<?php

declare(strict_types=1);

namespace App\Models\Infos;

use App\Enums\PositionInPageEnum;
use App\Models\Trait\HasSlugsTrait;
use App\Models\Trait\SlugTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfoPage extends Model
{
    use HasSlugsTrait, SlugTrait;

    protected $fillable = [
        'positions',
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
            'positions'  => 'array',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
            'is_noindex' => 'boolean',
        ];
    }

    public function infoPageDescription(): HasMany
    {
        return $this->hasMany(InfoPageDescription::class);
    }

    public function positions(): Attribute
    {
        return Attribute::make(
            get: function (?string $positions) {
                if (empty($positions)) {
                    return [];
                }

                return array_map(function (string $position) {
                    return PositionInPageEnum::tryFrom($position);
                }, json_decode($positions, true));
            },
            set: function (null|array|string $positions) {
                if (empty($positions)) {
                    return;
                }

                $res = array_map(function (string $position) {
                    return PositionInPageEnum::tryFrom($position)?->value;
                }, is_string($positions) ? [$positions] : $positions);

                $res = array_filter($res);

                return $res ? json_encode($res) : null;
            },
        );
    }
}
