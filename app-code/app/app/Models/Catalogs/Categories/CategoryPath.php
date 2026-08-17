<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Categories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryPath extends Model
{
    protected $fillable = [
        'category_id',
        'path_id',
        'level',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'path_id' => 'integer',
            'level' => 'integer',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** @return Collection<int, self> */
    public function getPathIdsByCategoryId(int $category_id): Collection
    {
        return self::query()
            ->where('category_id', $category_id)
            ->orderBy('level', 'desc')
            ->get();
    }
}
