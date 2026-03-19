<?php

declare(strict_types=1);

namespace App\Models\PageSettings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageSetting extends Model
{
    public const string PAGE_TYPE_CATEGORY = 'category';

    public const string ITEM_TYPE_SORTING = 'sorting';

    public const string ITEM_TYPE_FILTER = 'filter';

    protected $fillable = [
        'page_type',
        'is_sorting_enabled',
        'is_filtering_enabled',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_sorting_enabled'   => 'boolean',
            'is_filtering_enabled' => 'boolean',
            'settings'             => 'array',
        ];
    }

    /**
     * @return HasMany<PageSettingTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PageSettingTranslation::class);
    }

    /**
     * @return HasMany<PageSettingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PageSettingItem::class);
    }

    /**
     * @return HasMany<PageSettingItem, $this>
     */
    public function sortingItems(): HasMany
    {
        return $this->items()->where('type', self::ITEM_TYPE_SORTING);
    }

    /**
     * @return HasMany<PageSettingItem, $this>
     */
    public function filterItems(): HasMany
    {
        return $this->items()->where('type', self::ITEM_TYPE_FILTER);
    }
}
