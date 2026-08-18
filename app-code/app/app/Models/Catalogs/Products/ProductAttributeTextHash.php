<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Trait\OpenAiRelationsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeTextHash extends Model
{
    use OpenAiRelationsTrait;

    protected $fillable = [
        'product_id',
        'attribute_id',
        'hash',
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'attribute_id' => 'integer',
            'hash' => 'string',
        ];
    }

    /**
     * @phpstan-return BelongsTo<Product, $this>
     * @psalm-return BelongsTo<Product, self>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @phpstan-return BelongsTo<Attribute, $this>
     * @psalm-return BelongsTo<Attribute, self>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public static function getAttributeTextHash(int $product_id, int $attribute_id, string $hash): ?self
    {
        return self::query()
            ->where('product_id', $product_id)
            ->where('attribute_id', $attribute_id)
            ->where('hash', $hash)
            ->first();
    }
}
