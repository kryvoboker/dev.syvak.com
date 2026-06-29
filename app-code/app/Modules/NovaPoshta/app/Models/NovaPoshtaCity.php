<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NovaPoshtaCity extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nova_poshta_region_id',
        'ref',
        'region',
        'description',
        'city_name',
        'latitude',
        'longitude',
        'city_id',
        'region_description',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'nova_poshta_region_id' => 'integer',
            'city_id'               => 'integer',
        ];
    }

    /**
     * @return BelongsTo<NovaPoshtaRegion, $this>
     */
    public function novaPoshtaRegion(): BelongsTo
    {
        return $this->belongsTo(NovaPoshtaRegion::class);
    }

    /**
     * @return HasMany<NovaPoshtaPostOffice, $this>
     */
    public function novaPoshtaPostOffice(): HasMany
    {
        return $this->hasMany(NovaPoshtaPostOffice::class);
    }

    /**
     * @return HasMany<NovaPoshtaPoshtomat, $this>
     */
    public function novaPoshtaPoshtoman(): HasMany
    {
        return $this->hasMany(NovaPoshtaPoshtomat::class);
    }

    // protected static function newFactory(): NovaPoshtaCityFactory
    // {
    //     // return NovaPoshtaCityFactory::new();
    // }
}
