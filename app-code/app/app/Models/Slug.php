<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\ApplicationSettings\Language;
use Database\Factories\SlugFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $sluggable_id
 * @property string $sluggable_type
 * @property int $language_id
 * @property string $slug
 * @property-read Model $sluggable
 */
class Slug extends Model
{
    /** @use HasFactory<SlugFactory> */
    use HasFactory;

    protected $fillable = [
        'sluggable_id',
        'sluggable_type',
        'language_id',
        'slug',
    ];

    /** @phpstan-return MorphTo<Model, $this>
     * @psalm-return MorphTo<Model, self>
     */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @phpstan-return BelongsTo<Language, $this>
     * @psalm-return BelongsTo<Language, self>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
