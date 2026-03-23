<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(Request $request): View|Factory
    {
        $header_data           = app(HeaderService::class)();
        $page_type             = try_detect_page_type($request);
        $page_setting          = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();
        $page_setting_settings = is_array($page_setting->settings) ? $page_setting->settings : [];

        $category_page_settings = [
            'products_per_page_limit' => max(
                1,
                (int) Arr::get(
                    $page_setting_settings,
                    'pagination.products_per_page_limit',
                    (int) config('app.page_settings.category.products_per_page_limit', 20),
                ),
            ),
            'is_ajax_products_loading_enabled' => (bool) Arr::get(
                $page_setting_settings,
                'pagination.ajax_products_loading_enabled',
                (bool) config('app.page_settings.category.ajax_products_loading_enabled', true),
            ),
        ];

        $data = [
            'header_data' => $header_data,
            'footer_data' => app(FooterService::class)([
                // Footer uses category links too; pass already loaded categories from header.
                'categories' => $header_data['categories'],
            ]),
            'page_type'              => $page_type,
            'category_page_settings' => $category_page_settings,
        ];

        return view('catalog.pages.category', $data);
    }
}
