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
     * @return array<string, \Stringable|string>
     */
    protected function casts(): array
    {
        return [
            'attribute_id' => 'integer',
            'language_id' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<Attribute, $this>
     * @psalm-return BelongsTo<Attribute, self>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
