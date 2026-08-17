<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\ApplicationSettings\Language;
use Database\Factories\SlugFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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

    /** @return MorphTo<Model, $this> */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
