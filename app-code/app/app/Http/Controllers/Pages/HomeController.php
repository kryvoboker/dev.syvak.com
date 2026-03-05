<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\FooterService;
use App\Services\HeaderService;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View|Factory
    {
        $header_data = app(HeaderService::class)();

        $data = [
            'header_data' => $header_data,
            'footer_data' => app(FooterService::class)([
                // Footer uses category links too; pass already loaded categories from header.
                'categories' => $header_data['categories'],
            ]),
            'page_type' => try_detect_page_type(),
        ];

        return view('catalog.pages.home', $data);
    }
}
