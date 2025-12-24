<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\HeaderService;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * @return View|Factory
     */
    public function index(): View|Factory
    {
        $data = [
            'header_data' => app(HeaderService::class)(),
            'page_type'   => try_detect_page_type()
        ];

        return view('catalog.pages.home', $data);
    }
}
