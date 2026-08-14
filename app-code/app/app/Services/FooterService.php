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

    public function __invoke(array $params = []): array
    {
        $app_settings = get_app_settings();
        $logo_sizes = $app_settings->image_sizes?->firstWhere('name', 'logo') ?? [];
        $logo_width = (int) ($logo_sizes['width'] ?? config('app.images.logo_width'));
        $logo_height = (int) ($logo_sizes['height'] ?? config('app.images.logo_height'));
        $logo_path = (string) data_get(
            $app_settings,
            'system_settings.images.path_to_logo',
            (string) config('app.images.path_to_logo', 'images/logo.png'),
        );

        /** @var Collection<Category>|SupportCollection<Category> $categories */
        $categories = $params['categories'] ?? (new Category())->getActiveCategoriesWithDescriptionsAndSlugsByLanguageId(
            $app_settings->language_id,
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

    private function getSubscriptionData(array $social_items): array
    {
        $locale = app()->getLocale();
        $telegram_row = collect($social_items)
            ->first(fn (mixed $social_item): bool => (string) data_get($social_item, 'social_type') === 'telegram');
        $telegram_url = $this->normalizeSocialUrl(data_get($telegram_row, 'url'), $locale);

        return [
            'title' => __('storefront/footer.texts.subscribe'),
            'text' => __('storefront/footer.buttons.telegram'),
            'url' => filled($telegram_url) ? $telegram_url : '#',
            'support_text' => __('storefront/footer.texts.support'),
        ];
    }

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
     * @param  Collection<int, Category|array<string, mixed>>|SupportCollection<int, Category|array<string, mixed>>  $categories
     */
    private function getMenuItems(Collection|SupportCollection $categories): array
    {
        return $categories
            ->map(function (Category|array $category): array {
                $label = $category instanceof Category
                    ? (string) $category->categoryDescription->first()?->name
                    : (string) Arr::get($category, 'descriptions.name');

                $slug = $category instanceof Category
                    ? (string) $category->slugs->first()?->slug
                    : (string) Arr::get($category, 'slug');

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

    private function getSocialItems(): array
    {
        $locale = app()->getLocale();

        $social_items = collect(data_get(get_app_settings(), "socials.$locale", []))
            ->map(function (mixed $item) use ($locale): array {
                $social_type = (string) data_get($item, 'social_type');
                $label = Str::title($social_type);
                $social_url = $this->normalizeSocialUrl(data_get($item, 'url'), $locale);

                return [
                    'social_type' => $social_type,
                    'url' => filled($social_url) ? $social_url : '#',
                    'svg_icon' => escape_special_html((string) data_get($item, 'svg_icon')),
                    'label' => filled($label) ? $label : 'Link',
                ];
            })
            ->filter(fn (array $item) => filled($item['url']))
            ->values()
            ->all();

        return filled($social_items) ? $social_items : $this->getFallbackSocialItems();
    }

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

    private function parsePhones(mixed $phones): array
    {
        if (is_iterable($phones)) {
            $phones = collect($phones)
                ->map(
                    fn (mixed $phone): string => is_array($phone)
                    ? (string) data_get($phone, 'value', '')
                    : (string) $phone,
                )
                ->implode(',');
        }

        $phone_list = trim_strs_in_arr(explode(',', (string) $phones));
        $phone_list = collect($phone_list)
            ->filter(fn ($phone) => filled($phone))
            ->values()
            ->all();

        return filled($phone_list) ? $phone_list : ['0 800 000 000'];
    }
}
