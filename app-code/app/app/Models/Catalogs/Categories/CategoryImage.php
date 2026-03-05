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
        'icon',
        'sort_order',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'sort_order'  => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
