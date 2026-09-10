<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UkrPoshtaCity extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ukr_poshta_region_id',
        'ukr_poshta_district_id',
        'city_id',
        'description',
        'city_ua',
        'latitude',
        'longitude',
        'region_ua',
        'district_ua',
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'ukr_poshta_region_id' => 'integer',
            'ukr_poshta_district_id' => 'integer',
            'city_id' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<UkrPoshtaRegion, $this>
     * @psalm-return BelongsTo<UkrPoshtaRegion, self>
     */
    public function ukrPoshtaRegion(): BelongsTo
    {
        return $this->belongsTo(UkrPoshtaRegion::class, 'ukr_poshta_region_id', 'region_id');
    }

    /**
     * @phpstan-return BelongsTo<UkrPoshtaDistrict, $this>
     * @psalm-return BelongsTo<UkrPoshtaDistrict, self>
     */
    public function ukrPoshtaDistrict(): BelongsTo
    {
        return $this->belongsTo(UkrPoshtaDistrict::class, 'ukr_poshta_district_id', 'district_id');
    }

    /**
     * @phpstan-return HasMany<UkrPoshtaPostOffice, $this>
     * @psalm-return HasMany<UkrPoshtaPostOffice, self>
     */
    public function ukrPoshtaPostOffice(): HasMany
    {
        return $this->hasMany(UkrPoshtaPostOffice::class, 'pdcity_id', 'city_id');
    }

    // protected static function newFactory(): UkrPoshtaCityFactory
    // {
    //     // return UkrPoshtaCityFactory::new();
    // }
}
