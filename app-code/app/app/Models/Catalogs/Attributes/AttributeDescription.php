<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Attributes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeDescription extends Model
{
    protected $fillable = [
        'attribute_id',
        'language_id',
        'name',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'attribute_id' => 'integer',
            'language_id'  => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
