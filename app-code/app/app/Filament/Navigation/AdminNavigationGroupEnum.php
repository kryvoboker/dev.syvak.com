<?php

declare(strict_types=1);

namespace App\Filament\Navigation;

use Filament\Support\Contracts\HasLabel;

enum AdminNavigationGroupEnum: string implements HasLabel
{
    case Catalog = 'catalog';
    case InfoPages = 'info_pages';
    case Users = 'users';
    case Marketing = 'marketing';
    case Orders = 'orders';
    case Modules = 'modules';
    case Wiki = 'wiki';
    case PageSettings = 'page_settings';
    case ApplicationSettings = 'application_settings';

    public function getLabel(): string
    {
        return match ($this) {
            self::Catalog => __('admin/default.menu.item_catalog'),
            self::InfoPages => __('admin/default.menu.info_pages'),
            self::Users => __('admin/default.menu.item_users'),
            self::Marketing => __('admin/default.menu.item_marketing'),
            self::Orders => __('admin/default.menu.item_orders'),
            self::Modules => __('admin/default.menu.item_modules'),
            self::Wiki => __('admin/default.menu.wiki'),
            self::ApplicationSettings => __('admin/default.menu.item_application_settings'),
            self::PageSettings => __('admin/default.menu.item_page_settings'),
        };
    }
}
