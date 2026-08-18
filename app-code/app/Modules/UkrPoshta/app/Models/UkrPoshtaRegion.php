<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UkrPoshtaRegion extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'region_id',
        'region_ua',
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'region_id' => 'integer',
        ];
    }

    /**
     * @phpstan-return HasMany<UkrPoshtaDistrict, $this>
     * @psalm-return HasMany<UkrPoshtaDistrict, self>
     */
    public function ukrPoshtaDistricts(): HasMany
    {
        return $this->hasMany(UkrPoshtaDistrict::class, 'ukr_poshta_region_id', 'region_id');
    }

    /**
     * @phpstan-return HasMany<UkrPoshtaCity, $this>
     * @psalm-return HasMany<UkrPoshtaCity, self>
     */
    public function ukrPoshtaCities(): HasMany
    {
        return $this->hasMany(UkrPoshtaCity::class, 'ukr_poshta_region_id', 'region_id');
    }

    /**
     * @phpstan-return HasMany<UkrPoshtaPostOffice, $this>
     * @psalm-return HasMany<UkrPoshtaPostOffice, self>
     */
    public function ukrPoshtaPostOffices(): HasMany
    {
        return $this->hasMany(UkrPoshtaPostOffice::class, 'poregion_id', 'region_id');
    }

    // protected static function newFactory(): UkrPoshtaRegionFactory
    // {
    //     // return UkrPoshtaRegionFactory::new();
    // }
}
