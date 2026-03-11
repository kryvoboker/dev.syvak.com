<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\FooterService;
use App\Services\HeaderService;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Modules\Carousel\Services\CarouselModuleDataService;

class HomeController extends Controller
{
    public function index(): View|Factory
    {
        $header_data = app(HeaderService::class)();
        $page_type   = try_detect_page_type();

        $data = [
            'header_data'           => $header_data,
            'footer_data'           => app(FooterService::class)([
                // Footer uses category links too; pass already loaded categories from header.
                'categories' => $header_data['categories'],
            ]),
            'carousel_modules_data' => app(CarouselModuleDataService::class)
                ->resolveForPlacement(config('app.modules_placements.top'), $page_type),
            'page_type'             => $page_type,
        ];

        return view('catalog.pages.home', $data);
    }
}
