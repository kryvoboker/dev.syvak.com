<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovaPoshtaPostOffice extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nova_poshta_city_id',
        'ref',
        'city_ref',
        'description',
        'latitude',
        'longitude',
        'schedule',
        'number',
        'city_description',
        'site_key',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'nova_poshta_city_id' => 'integer',
            'number'              => 'integer',
            'site_key'            => 'integer',
        ];
    }

    /**
     * @return BelongsTo<NovaPoshtaCity, $this>
     */
    public function novaPoshtaCity(): BelongsTo
    {
        return $this->belongsTo(NovaPoshtaCity::class);
    }

    // protected static function newFactory(): NovaPoshtaPostOfficeFactory
    // {
    //     // return NovaPoshtaPostOfficeFactory::new();
    // }
}
