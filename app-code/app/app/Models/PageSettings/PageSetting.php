<?php

declare(strict_types=1);

namespace App\Models\PageSettings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class PageSetting extends Model
{
    public const string PAGE_TYPE_CATEGORY = 'category';

    public const string PAGE_TYPE_PRODUCT = 'product';

    public const string PAGE_TYPE_SEARCH = 'search';

    protected $fillable = [
        'page_type',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSortingItemsFromSettings(): array
    {
        $settings       = is_array($this->settings) ? $this->settings : [];
        $settings_items = Arr::get($settings, 'items.sorting', []);

        if (! is_array($settings_items)) {
            return [];
        }

        return collect($settings_items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => $item)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFilterItemsFromSettings(): array
    {
        $settings       = is_array($this->settings) ? $this->settings : [];
        $settings_items = Arr::get($settings, 'items.filters', []);

        if (! is_array($settings_items)) {
            return [];
        }

        return collect($settings_items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => $item)
            ->values()
            ->all();
    }
}
