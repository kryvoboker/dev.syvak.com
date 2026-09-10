<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $language_id
 * @property string|null $title
 * @property array<int, mixed>|null $items
 */
class ProductComposition extends Model
{
    protected $fillable = ['product_id', 'language_id', 'title', 'items'];

    #[\Override]
    protected function casts(): array
    {
        return ['product_id' => 'integer', 'language_id' => 'integer', 'items' => 'array'];
    }

    /** @phpstan-return BelongsTo<Product, $this>
     * @psalm-return BelongsTo<Product, self>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @phpstan-return BelongsTo<Language, $this>
     * @psalm-return BelongsTo<Language, self>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
