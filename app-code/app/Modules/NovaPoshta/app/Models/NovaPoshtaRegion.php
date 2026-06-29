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
    public function novaPoshtaCity(): HasMany
    {
        return $this->hasMany(NovaPoshtaCity::class);
    }

    // protected static function newFactory(): NovaPoshtaRegionFactory
    // {
    //     // return NovaPoshtaRegionFactory::new();
    // }
}
