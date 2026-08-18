<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Categories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryImage extends Model
{
    protected $fillable = [
        'category_id',
        'preview_image',
        'preview_image_width',
        'preview_image_height',
        'icon',
        'sort_order',
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'preview_image_width' => 'integer',
            'preview_image_height' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<Category, $this>
     * @psalm-return BelongsTo<Category, self>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
