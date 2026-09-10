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
     * @return array<string, \Stringable|string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'nova_poshta_region_id' => 'integer',
            'city_id' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<NovaPoshtaRegion, $this>
     * @psalm-return BelongsTo<NovaPoshtaRegion, self>
     */
    public function novaPoshtaRegion(): BelongsTo
    {
        return $this->belongsTo(NovaPoshtaRegion::class, 'nova_poshta_region_id');
    }

    /**
     * @phpstan-return HasMany<NovaPoshtaPostOffice, $this>
     * @psalm-return HasMany<NovaPoshtaPostOffice, self>
     */
    public function novaPoshtaPostOffices(): HasMany
    {
        return $this->hasMany(NovaPoshtaPostOffice::class, 'nova_poshta_city_id');
    }

    /**
     * @phpstan-return HasMany<NovaPoshtaPoshtomat, $this>
     * @psalm-return HasMany<NovaPoshtaPoshtomat, self>
     */
    public function novaPoshtaPoshtomats(): HasMany
    {
        return $this->hasMany(NovaPoshtaPoshtomat::class, 'nova_poshta_city_id');
    }

    // protected static function newFactory(): NovaPoshtaCityFactory
    // {
    //     // return NovaPoshtaCityFactory::new();
    // }
}
