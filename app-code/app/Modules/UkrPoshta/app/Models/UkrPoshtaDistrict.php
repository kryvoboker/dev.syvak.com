<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UkrPoshtaDistrict extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ukr_poshta_region_id',
        'district_id',
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
            'district_id' => 'integer',
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
     * @phpstan-return HasMany<UkrPoshtaCity, $this>
     * @psalm-return HasMany<UkrPoshtaCity, self>
     */
    public function ukrPoshtaCities(): HasMany
    {
        return $this->hasMany(UkrPoshtaCity::class, 'ukr_poshta_district_id', 'district_id');
    }

    /**
     * @phpstan-return HasMany<UkrPoshtaPostOffice, $this>
     * @psalm-return HasMany<UkrPoshtaPostOffice, self>
     */
    public function ukrPoshtaPostOffices(): HasMany
    {
        return $this->hasMany(UkrPoshtaPostOffice::class, 'podistrict_id', 'district_id');
    }

    // protected static function newFactory(): UkrPoshtaDistrictFactory
    // {
    //     // return UkrPoshtaDistrictFactory::new();
    // }
}
