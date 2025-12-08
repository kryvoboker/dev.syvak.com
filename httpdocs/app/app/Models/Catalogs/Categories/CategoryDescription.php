<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Categories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryDescription extends Model
{
    protected $fillable = [
        'category_id',
        'language_id',
        'name',
        'description',
        'h1_title',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'language_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
