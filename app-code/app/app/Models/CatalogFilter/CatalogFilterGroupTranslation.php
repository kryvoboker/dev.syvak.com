<?php

declare(strict_types=1);

namespace App\Models\CatalogFilter;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogFilterGroupTranslation extends Model
{
    protected $fillable = [
        'catalog_filter_group_id',
        'language_id',
        'label',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'catalog_filter_group_id' => 'integer',
            'language_id'             => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CatalogFilterGroup, $this>
     */
    public function filterGroup(): BelongsTo
    {
        return $this->belongsTo(CatalogFilterGroup::class, 'catalog_filter_group_id');
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
