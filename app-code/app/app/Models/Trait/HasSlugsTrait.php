<?php

declare(strict_types=1);

namespace App\Models\Trait;

use App\Models\Slug;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSlugsTrait
{
    /**
     * @return MorphMany<Slug>
     */
    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable');
    }

    /**
     * Get slug for specific language
     *
     * @param int $language_id
     * @return string|null
     */
    public function getSlugByLanguageId(int $language_id): ?string
    {
        return $this
            ->slugs()
            ->where('language_id', $language_id)
            ->value('slug');
    }

    /**
     * Find model by slug and language
     *
     * @param string $slug
     * @param int $language_id
     * @return static|null
     */
    public static function findBySlug(string $slug, int $language_id): ?static
    {
        $slug_record = Slug::query()
            ->where('slug', $slug)
            ->where('language_id', $language_id)
            ->where('sluggable_type', static::class)
            ->first();

        return $slug_record?->sluggable;
    }
}
