<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovaPoshtaPoshtomat extends Model
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
     * @return array<string, \Stringable|string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'nova_poshta_city_id' => 'integer',
            'number' => 'integer',
            'site_key' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<NovaPoshtaCity, $this>
     * @psalm-return BelongsTo<NovaPoshtaCity, self>
     */
    public function novaPoshtaCity(): BelongsTo
    {
        return $this->belongsTo(NovaPoshtaCity::class, 'nova_poshta_city_id');
    }

    // protected static function newFactory(): NovaPoshtaPostomatFactory
    // {
    //     // return NovaPoshtaPostomatFactory::new();
    // }
}
