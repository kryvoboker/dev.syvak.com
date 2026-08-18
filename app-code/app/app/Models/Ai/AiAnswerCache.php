<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiAnswerCache extends Model
{
    protected $fillable = [
        'hashable_type',
        'hashable_id',
        'prompt',
        'answer',
    ];

    /** @phpstan-return MorphTo<Model, $this>
     * @psalm-return MorphTo<Model, self>
     */
    public function hashable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, \Stringable|string>
     */
    protected function casts(): array
    {
        return [
            'hashable_id' => 'integer',
        ];
    }
}
