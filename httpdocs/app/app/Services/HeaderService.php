<?php

declare(strict_types=1);

namespace App\Services;

class HeaderService
{
    public function __invoke()
    {
        $app_settings = app(AppSettingsService::class)->getSettings();

        $data = [
            'logo_url' => img_cached_url(
                config('app.images.path_to_logo'),
                (int)($app_settings->image_sizes['logo']['width'] ?? config('app.images.logo_width')),
                (int)($app_settings->image_sizes['logo']['height'] ?? config('app.images.logo_height'))
            ),
        ];
    }
}
