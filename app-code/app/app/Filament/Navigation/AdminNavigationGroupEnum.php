<?php

declare(strict_types=1);

namespace App\Filament\Navigation;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum AdminNavigationGroupEnum: string implements HasLabel
{
    case Catalog             = 'catalog';
    case InfoPages           = 'info_pages';
    case Users               = 'users';
    case Modules             = 'modules';
    case PageSettings        = 'page_settings';
    case ApplicationSettings = 'application_settings';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Catalog             => __('admin/default.menu.item_catalog'),
            self::InfoPages           => __('admin/default.menu.info_pages'),
            self::Users               => __('admin/default.menu.item_users'),
            self::Modules             => __('admin/default.menu.item_modules'),
            self::ApplicationSettings => __('admin/default.menu.item_application_settings'),
            self::PageSettings        => __('admin/default.menu.item_page_settings'),
        };
    }
}
