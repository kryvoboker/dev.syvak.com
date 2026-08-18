<?php

declare(strict_types=1);

namespace App\Models\Trait;

use App\Models\Ai\AiAnswerCache;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait OpenAiRelationsTrait
{
    /**
     * @phpstan-return MorphOne<AiAnswerCache, $this>
     * @psalm-return MorphOne<AiAnswerCache, self>
     */
    public function aiAnswerCache(): MorphOne
    {
        return $this->morphOne(AiAnswerCache::class, 'hashable');
    }
}
