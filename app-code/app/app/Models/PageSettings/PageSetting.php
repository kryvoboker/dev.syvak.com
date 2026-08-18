<?php

declare(strict_types=1);

namespace App\Models\PageSettings;

use App\Models\Trait\HasSlugsTrait;
use App\Models\Trait\SlugTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * @property array<string, mixed>|null $settings
 */
class PageSetting extends Model
{
    use HasSlugsTrait;
    use SlugTrait;

    public const PAGE_TYPE_CATEGORY = 'category';

    public const PAGE_TYPE_PRODUCT = 'product';

    public const PAGE_TYPE_SEARCH = 'search';

    public const PAGE_TYPE_NOT_FOUND = 'not_found';

    public const PAGE_TYPE_CONTACTS = 'contacts';

    public const PAGE_TYPE_FAILURE = 'failure';

    protected $fillable = [
        'page_type',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
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
        $settings = is_array($this->settings) ? $this->settings : [];
        $settings_items = Arr::get($settings, 'items.sorting', []);

        if (! is_array($settings_items)) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $items */
        $items = collect($settings_items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => $item)
            ->values()
            ->all();

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFilterItemsFromSettings(): array
    {
        $settings = is_array($this->settings) ? $this->settings : [];
        $settings_items = Arr::get($settings, 'items.filters', []);

        if (! is_array($settings_items)) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $items */
        $items = collect($settings_items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => $item)
            ->values()
            ->all();

        return $items;
    }
}
