<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Settings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Slug extends Model
{
    protected $fillable = [
        'sluggable_id',
        'sluggable_type',
        'language_id',
        'slug',
    ];

    /**
     * @return MorphTo
     */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Language>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
