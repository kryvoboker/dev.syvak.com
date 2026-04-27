<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\Trait\CartTrait;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    use CartTrait;

    /**
     * @param Request     $request
     * @param string|null $locale
     *
     * @return View
     */
    public function index(Request $request, ?string $locale): View
    {
        $locale = normalize_locale($locale);

        $data = [];

        return view('catalog.pages.cart', $data);
    }
}
