<?php

declare(strict_types=1);

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol_left',
        'symbol_right',
        'decimal_places',
        'exchange_rate',
        'is_active',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'exchange_rate'  => 'decimal:6',
            'is_active'      => 'boolean',
        ];
    }
}
