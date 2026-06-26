<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use App\Services\FooterService;
use App\Services\HeaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(?string $locale): View|RedirectResponse
    {
        $locale    = normalize_locale($locale);
        $cart_data = app(CartService::class)->getSnapshot($locale);

        if (($cart_data['is_empty'] ?? true) === true) {
            return redirect()->to(localized_route('localized.catalog.home'));
        }

        $header_data = app(HeaderService::class)();
        $footer_data = app(FooterService::class)([
            'categories' => $header_data['categories'],
        ]);

        $cart_items         = array_values(is_array($cart_data['items'] ?? null) ? $cart_data['items'] : []);
        $visible_cart_items = array_slice($cart_items, 0, 2);
        $hidden_cart_items  = array_slice($cart_items, 2);

        return view('catalog.pages.checkout', [
            'header_data'   => $header_data,
            'footer_data'   => $footer_data,
            'page_type'     => 'checkout',
            'cart_data'     => $cart_data,
            'checkout_data' => [
                'edit_items_url'        => localized_route('localized.catalog.cart.index'),
                'items_count'           => count($cart_items),
                'visible_items'         => $visible_cart_items,
                'hidden_items'          => $hidden_cart_items,
                'subtotal_formatted'    => (string)($cart_data['totals']['grand_total_formatted'] ?? ''),
                'delivery_formatted'    => '—',
                'grand_total_formatted' => (string)($cart_data['totals']['grand_total_formatted'] ?? ''),
            ],
        ]);
    }
}
