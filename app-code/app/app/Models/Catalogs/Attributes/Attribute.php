<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Attributes;

use App\Models\Catalogs\Products\ProductAttributeTextHash;
use App\Models\Catalogs\Products\ProductVariantAttributeValue;
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProductAttributeTextHash, $this>
     */
    public function productAttributeTextHash(): HasMany
    {
        return $this->hasMany(ProductAttributeTextHash::class);
    }

    /**
     * @return HasMany<AttributeDescription, $this>
     */
    public function attributeDescription(): HasMany
    {
        return $this->hasMany(AttributeDescription::class);
    }

    /**
     * Compatibility relation for legacy naming in services.
     *
     * @return HasMany<ProductVariantAttributeValue, $this>
     */
    public function productToAttribute(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class, 'attribute_id');
    }

    /**
     * @return HasMany<ProductVariantAttributeValue, $this>
     */
    public function productVariantAttributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class, 'attribute_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (Attribute $attribute): void {
            $attribute->attributeDescription()->delete();
        });
    }

    /** @return Collection<int, self> */
    public function getActiveAttributesWithDescriptionsByLanguageId(int $language_id): Collection
    {
        return self::query()
            ->where('is_active', true)
            ->with([
                'attributeDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->get();
    }

    public function getActiveAttributeWithDescriptionByAttributeIdAndLanguageId(int $attribute_id, int $language_id): ?self
    {
        return self::with([
            'attributeDescription' => function ($query) use ($language_id): void {
                $query->where('language_id', $language_id);
            },
        ])
            ->where('is_active', true)
            ->find($attribute_id);
    }
}
