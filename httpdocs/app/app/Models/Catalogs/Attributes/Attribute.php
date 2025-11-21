<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Attributes;

use App\Models\Catalogs\Products\ProductToAttribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'sort_order',
        'is_active',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    /**
     * @return HasMany<AttributeDescription>
     */
    public function attributeDescription(): HasMany
    {
        return $this->hasMany(AttributeDescription::class);
    }

    /**
     * @return HasMany<ProductToAttribute>
     */
    public function productToAttribute(): HasMany
    {
        return $this->hasMany(ProductToAttribute::class);
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        // Delete related translations when attribute is deleted
        static::deleting(function (Attribute $attribute) {
            $attribute->attributeDescription()->delete();
        });
    }

    /**
     * @param int $language_id
     *
     * @return Collection
     */
    public function getActiveAttributesWithDescriptionsByLanguageId(int $language_id): Collection
    {
        return self::query()
            ->where('is_active', true)
            ->with([
                'attributeDescription' => function ($query) use ($language_id) {
                    $query->where('language_id', $language_id);
                }
            ])
            ->get();
    }
}
