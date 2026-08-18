<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UkrPoshtaPostOffice extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'poregion_id',
        'podistrict_id',
        'pdcity_id',
        'description',
        'latitude',
        'longitude',
        'lock_code',
        'postcode',
        'region_ua',
        'district_ua',
        'postreet_id',
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'poregion_id' => 'integer',
            'podistrict_id' => 'integer',
            'pdcity_id' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<UkrPoshtaRegion, $this>
     * @psalm-return BelongsTo<UkrPoshtaRegion, self>
     */
    public function ukrPoshtaRegion(): BelongsTo
    {
        return $this->belongsTo(UkrPoshtaRegion::class, 'poregion_id', 'region_id');
    }

    /**
     * @phpstan-return BelongsTo<UkrPoshtaDistrict, $this>
     * @psalm-return BelongsTo<UkrPoshtaDistrict, self>
     */
    public function ukrPoshtaDistrict(): BelongsTo
    {
        return $this->belongsTo(UkrPoshtaDistrict::class, 'podistrict_id', 'district_id');
    }

    /**
     * @phpstan-return BelongsTo<UkrPoshtaCity, $this>
     * @psalm-return BelongsTo<UkrPoshtaCity, self>
     */
    public function ukrPoshtaCity(): BelongsTo
    {
        return $this->belongsTo(UkrPoshtaCity::class, 'pdcity_id', 'city_id');
    }

    // protected static function newFactory(): UkrPoshtaPostOfficeFactory
    // {
    //     // return UkrPoshtaPostOfficeFactory::new();
    // }
}
