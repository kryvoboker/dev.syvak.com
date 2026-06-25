<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\Trait\CartTrait;
use Illuminate\View\View;

class CartController extends Controller
{
    use CartTrait;

    public function index(?string $locale): View
    {
        $locale      = normalize_locale($locale);
        $header_data = app(HeaderService::class)();
        $cart_data   = app(CartService::class)->getSnapshot($locale);

        $data = [
            'header_data' => $header_data,
            'footer_data' => app(FooterService::class)([
                'categories' => $header_data['categories'],
            ]),
            'page_type'   => 'cart',
            'breadcrumbs' => [
                breadcrumb(__('catalog/default.links.home'), localized_route('catalog.home')),
                breadcrumb(__('catalog/default.cart.labels.cart')),
            ],
            'cart_data' => $cart_data,
        ];

        return view('catalog.pages.cart', $data);
    }
}
