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
    use HasSlugsTrait;
    use SlugTrait;

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
            'positions' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_noindex' => 'boolean',
        ];
    }

    /** @return HasMany<InfoPageDescription, $this> */
    public function infoPageDescription(): HasMany
    {
        return $this->hasMany(InfoPageDescription::class);
    }

    /** @return Attribute<mixed, mixed> */
    public function positions(): Attribute
    {
        return Attribute::make(
            get: function (mixed $positions, array $attributes): array {
                if (is_string($positions)) {
                    $positions = json_decode($positions, true);
                }

                if (! is_array($positions)) {
                    return [];
                }

                return array_map(
                    fn (string $position): ?PositionInPageEnum => PositionInPageEnum::tryFrom($position),
                    array_values(array_filter($positions, is_string(...))),
                );
            },
            set: function (mixed $positions, array $attributes): ?string {
                if ($positions === null || $positions === '') {
                    return null;
                }

                $positions = is_string($positions) ? [$positions] : $positions;

                if (! is_array($positions)) {
                    return null;
                }

                $res = array_map(
                    fn (string $position): ?string => PositionInPageEnum::tryFrom($position)?->value,
                    array_values(array_filter($positions, is_string(...))),
                );

                $res = array_filter($res);

                if ($res === []) {
                    return null;
                }

                $encoded = json_encode($res);

                return is_string($encoded) ? $encoded : null;
            },
        );
    }
}
