<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\Trait\OpenAiRelationsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDescriptionHash extends Model
{
    use OpenAiRelationsTrait;

    protected $fillable = [
        'product_id',
        'hash',
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
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

    public static function getDescriptionHash(int $product_id, string $hash): ?self
    {
        return self::query()
            ->where('product_id', $product_id)
            ->where('hash', $hash)
            ->first();
    }
}
