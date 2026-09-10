<?php

declare(strict_types=1);

namespace App\Models\Infos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfoPageDescription extends Model
{
    protected $fillable = [
        'info_page_id',
        'language_id',
        'title',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'info_page_id' => 'integer',
            'language_id' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<InfoPage, $this>
     * @psalm-return BelongsTo<InfoPage, self>
     */
    public function infoPage(): BelongsTo
    {
        return $this->belongsTo(InfoPage::class);
    }
}
