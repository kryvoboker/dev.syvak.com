<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\Trait\CartTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    use CartTrait;

    public function index(?string $locale): View|RedirectResponse
    {
        $locale = normalize_locale($locale);
        $cart_data = app(CartService::class)->getSnapshot($locale);
        $is_cart_empty = (bool)($cart_data['is_empty'] ?? true);

        if ($is_cart_empty === true) {
            return redirect()->to(localized_route('catalog.home'));
        }

        $header_data = app(HeaderService::class)();

        $data = [
            'header_data' => $header_data,
            'footer_data' => app(FooterService::class)([
                'categories' => $header_data['categories'],
            ]),
            'page_type' => config('page-settings.page_type.cart', 'cart'),
            'breadcrumbs' => [
                breadcrumb(__('storefront/default.links.home'), localized_route('catalog.home')),
                breadcrumb(__('storefront/default.cart.labels.cart')),
            ],
            'cart_data' => $cart_data,
            'show_checkout_button' => true,
        ];

        return view('storefront.pages.cart', $data);
    }
}
