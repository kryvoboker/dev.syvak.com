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
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'ukr_poshta_region_id' => 'integer',
            'ukr_poshta_district_id' => 'integer',
            'city_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<UkrPoshtaRegion, $this>
     */
    public function ukrPoshtaRegion(): BelongsTo
    {
        return $this->belongsTo(UkrPoshtaRegion::class, 'ukr_poshta_region_id', 'region_id');
    }

    /**
     * @return BelongsTo<UkrPoshtaDistrict, $this>
     */
    public function ukrPoshtaDistrict(): BelongsTo
    {
        return $this->belongsTo(UkrPoshtaDistrict::class, 'ukr_poshta_district_id', 'district_id');
    }

    /**
     * @return HasMany<UkrPoshtaPostOffice, $this>
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
