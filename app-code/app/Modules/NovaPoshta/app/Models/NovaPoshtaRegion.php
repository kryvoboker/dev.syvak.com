<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NovaPoshtaRegion extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ref',
        'regions_center',
        'description',
    ];

    /**
     * @return HasMany<NovaPoshtaCity, $this>
     */
    public function novaPoshtaCities(): HasMany
    {
        return $this->hasMany(NovaPoshtaCity::class, 'nova_poshta_region_id');
    }

    // protected static function newFactory(): NovaPoshtaRegionFactory
    // {
    //     // return NovaPoshtaRegionFactory::new();
    // }
}
