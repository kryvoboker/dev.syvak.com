<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalogs\Categories\Category;
use App\Services\Trait\SocialServiceTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class FooterService
{
    use SocialServiceTrait;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function __invoke(array $params = []): array
    {
        $app_settings = get_app_settings() ?? throw new \LogicException('Application settings are not initialized.');
        $logo_sizes = $this->arrayValue($app_settings->image_sizes?->firstWhere('name', 'logo'));
        $logo_width = $this->integerValue($logo_sizes['width'] ?? config('app.images.logo_width'));
        $logo_height = $this->integerValue($logo_sizes['height'] ?? config('app.images.logo_height'));
        $logo_path = $this->stringValue(data_get(
            $app_settings,
            'system_settings.images.path_to_logo',
            $this->stringValue(config('app.images.path_to_logo', 'images/logo.png')),
        ));

        /** @var Collection<int, Category|array<string, mixed>>|SupportCollection<int, Category|array<string, mixed>> $categories */
        $categories = $params['categories'] ?? (new Category())->getActiveCategoriesWithDescriptionsAndSlugsByLanguageId(
            $app_settings->language_id ?? 0,
        );
        $social_items = $this->getSocialItems();

        return [
            'logo_data' => [
                'urls' => multiple_convert_img_and_get_url(
                    $logo_path,
                    $logo_width,
                    $logo_height,
                    is_square: false,
                ),
                'width' => $logo_width,
                'height' => $logo_height,
            ],
            'subscription_data' => $this->getSubscriptionData($social_items),
            'contacts_data' => $this->getContactsData(),
            'menu_items' => $this->getMenuItems($categories),
            'information_data' => $this->getInformationData(),
            'social_items' => $social_items,
            'brand_large_text' => 'SYVAK',
            'brand_compact_text' => 'SYVAK',
        ];
    }

    /** @param array<int, array<string, mixed>> $social_items
     * @return array<string, mixed>
     */
    private function getSubscriptionData(array $social_items): array
    {
        $locale = app()->getLocale();
        $telegram_row = collect($social_items)
            ->first(fn (mixed $social_item): bool => $this->stringValue(data_get($social_item, 'social_type')) === 'telegram');
        $telegram_url = $this->normalizeSocialUrl(data_get($telegram_row, 'url'), $locale);

        return [
            'title' => __('storefront/footer.texts.subscribe'),
            'text' => __('storefront/footer.buttons.telegram'),
            'url' => filled($telegram_url) ? $telegram_url : '#',
            'support_text' => __('storefront/footer.texts.support'),
        ];
    }

    /** @return array<string, mixed> */
    private function getContactsData(): array
    {
        return [
            'title' => '/ КОНТАКТИ /',
            'phones' => $this->parsePhones(get_app_settings()?->contact_phones),
            'find_us_label' => 'ДЕ НАС ЗНАЙТИ',
            'contacts_label' => 'КОНТАКТИ',
        ];
    }

    /**
     * @param Collection<int, Category|array<string, mixed>>|SupportCollection<int, Category|array<string, mixed>> $categories
     * @return array<int, array{label: string, url: string}>
     */
    private function getMenuItems(Collection|SupportCollection $categories): array
    {
        return $categories
            ->map(function (Category|array $category): array {
                $label = $category instanceof Category
                    ? $this->stringValue($category->categoryDescription->first()?->name)
                    : $this->stringValue(Arr::get($category, 'descriptions.name'));

                $slug = $category instanceof Category
                    ? $this->stringValue($category->slugs->first()?->slug)
                    : $this->stringValue(Arr::get($category, 'slug'));

                return [
                    'label' => Str::upper($label),
                    'url' => localized_route('localized.catalog.category.show', [
                        'slug' => $slug,
                    ]),
                ];
            })
            ->filter(fn (array $item): bool => filled($item['label']) && filled($item['url']))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function getInformationData(): array
    {
        return [
            'title' => '/ ІНФОРМАЦІЯ /',
            'items' => [
                ['label' => 'ПОЛІТИКА КОНФЕДЕНЦІЙНОСТІ', 'url' => '#'],
                ['label' => 'ДОСТАВКА ТА ПОВЕРНЕННЯ', 'url' => '#'],
                ['label' => 'УМОВИ ВИКОРИСТАННЯ', 'url' => '#'],
                ['label' => 'СПІВРОБІТНИЦТВО', 'url' => '#'],
                ['label' => 'ДОГОВІР ПУБЛІЧНОЇ ОФЕРТИ', 'url' => '#'],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function getSocialItems(): array
    {
        $locale = app()->getLocale();

        $social_items = collect((array) data_get(get_app_settings(), "socials.$locale", []))
            ->map(function (mixed $item) use ($locale): array {
                $social_type = $this->stringValue(data_get($item, 'social_type'));
                $label = Str::title($social_type);
                $social_url = $this->normalizeSocialUrl(data_get($item, 'url'), $locale);

                return [
                    'social_type' => $social_type,
                    'url' => filled($social_url) ? $social_url : '#',
                    'svg_icon' => escape_special_html($this->stringValue(data_get($item, 'svg_icon'))),
                    'label' => filled($label) ? $label : 'Link',
                ];
            })
            ->filter(fn (array $item) => filled($item['url']))
            ->values()
            ->all();

        return filled($social_items) ? $social_items : $this->getFallbackSocialItems();
    }

    /** @return array<int, array<string, string>> */
    private function getFallbackSocialItems(): array
    {
        return [
            [
                'social_type' => 'facebook',
                'url' => '#',
                'svg_icon' => '<span class="icon-[mdi--facebook] text-4xl"></span>',
                'label' => 'Facebook',
            ],
            [
                'social_type' => 'instagram',
                'url' => '#',
                'svg_icon' => '<span class="icon-[mdi--instagram] text-4xl"></span>',
                'label' => 'Instagram',
            ],
            [
                'social_type' => 'tiktok',
                'url' => '#',
                'svg_icon' => '<span class="icon-[ic--baseline-tiktok] text-4xl"></span>',
                'label' => 'TikTok',
            ],
        ];
    }

    /** @return list<string> */
    private function parsePhones(mixed $phones): array
    {
        if (is_iterable($phones)) {
            $phone_values = is_array($phones)
                ? $phones
                : iterator_to_array($phones);
            $phones = collect($phone_values)
                ->map(
                    fn (mixed $phone): string => is_array($phone)
                    ? $this->stringValue(data_get($phone, 'value', ''))
                    : $this->stringValue($phone),
                )
                ->implode(',');
        }

        $phone_list = trim_strs_in_arr(explode(',', $this->stringValue($phones)));
        $phone_list = collect($phone_list)
            ->filter(fn ($phone) => filled($phone))
            ->values()
            ->all();

        $phone_list = array_values(array_filter($phone_list, is_string(...)));

        return filled($phone_list) ? $phone_list : ['0 800 000 000'];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
