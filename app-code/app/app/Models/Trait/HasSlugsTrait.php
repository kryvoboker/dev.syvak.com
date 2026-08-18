<?php

declare(strict_types=1);

namespace App\Models\Trait;

use App\Models\Slug;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSlugsTrait
{
    /**
     * @phpstan-return MorphMany<Slug, $this>
     * @psalm-return MorphMany<Slug, self>
     */
    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable');
    }

    /**
     * Get slug for specific language
     */
    public function getSlugByLanguageId(int $language_id): ?string
    {
        $slug = $this
            ->slugs()
            ->where('language_id', $language_id)
            ->value('slug');

        return is_scalar($slug) ? (string) $slug : null;
    }

    /**
     * Find model by slug and language
     */
    public static function findBySlug(string $slug, int $language_id): ?static
    {
        $slug_record = Slug::query()
            ->where('slug', $slug)
            ->where('language_id', $language_id)
            ->where('sluggable_type', static::class)
            ->first();

        $sluggable = $slug_record?->sluggable;

        return $sluggable instanceof static ? $sluggable : null;
    }
}
