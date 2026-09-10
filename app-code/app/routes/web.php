<?php

declare(strict_types=1);

use App\Http\Controllers\Ajax\CartModalAjaxController;
use App\Http\Controllers\Ajax\CatalogFilterAjaxController;
use App\Http\Controllers\Ajax\LiveSearchProductsAjaxController;
use App\Http\Controllers\Ajax\LoadMoreProductsByAjaxController;
use App\Http\Controllers\Filament\Inquiries\DownloadInquiryAttachmentController;
use App\Http\Controllers\Frontend\FrontendErrorController;
use App\Http\Controllers\Order\OrderConfirmController;
use App\Http\Controllers\Pages\CartController;
use App\Http\Controllers\Pages\CategoryController;
use App\Http\Controllers\Pages\CheckoutController;
use App\Http\Controllers\Pages\ContactsController;
use App\Http\Controllers\Pages\FailureOrderController;
use App\Http\Controllers\Pages\HomeController;
use App\Http\Controllers\Pages\ProductController;
use App\Http\Controllers\Pages\SearchProductsController;
use App\Http\Controllers\Pages\ThankYouController;
use App\Http\Middleware\SetDefaultLocalePrefix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/' . app()->getLocale());

Route::get('/alyo-admin', function (): RedirectResponse {
    return redirect('/' . app()->getLocale() . '/alyo-admin');
});

Route::get('/alyo-admin/login', function (): RedirectResponse {
    return redirect('/' . app()->getLocale() . '/alyo-admin/login');
});

Route::get('/admin/inquiries/attachments/{attachment}/download', DownloadInquiryAttachmentController::class)
    ->name('admin.inquiries.attachments.download')
    ->middleware('auth');

Route::post('/frontend-errors', FrontendErrorController::class)
    ->middleware('throttle:frontend-errors')
    ->withoutMiddleware(SetDefaultLocalePrefix::class)
    ->name('frontend.errors.store');

$locale_key_value = config('localization.locale_parameter', 'locale');
$locale_key = is_scalar($locale_key_value) ? (string) $locale_key_value : 'locale';
$allowed_locales = get_allowed_locales();

require base_path('Modules/WayForPay/routes/web.php');

Route::prefix('{' . $locale_key . '}')
    ->whereIn($locale_key, $allowed_locales)
    ->name('localized.catalog.')
    ->group(function (): void {
        Route::get('/', [HomeController::class, 'index'])->name('home');

        Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');
        Route::get('/category/{slug}/filters', [CatalogFilterAjaxController::class, 'index'])->name('catalog-filter-ajax.index');
        Route::get('/category/{slug}/load-more', [LoadMoreProductsByAjaxController::class, 'index'])->name('load-more-products-ajax.index');

        Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
        Route::get('/product/{slug}/{variant_slug}', [ProductController::class, 'show'])->name('product.variant.show');
        Route::get('/product/static/{product_id}/{variant_id}', [ProductController::class, 'showStatic'])
            ->whereNumber(['product_id', 'variant_id'])
            ->name('product.static.show');

        Route::get('/live-search', [LiveSearchProductsAjaxController::class, 'index'])->name('live-search-product-ajax.index');
        Route::get('/search', [SearchProductsController::class, 'index'])->name('search-products.index');

        Route::get('/cart-modal', [CartModalAjaxController::class, 'index'])->name('cart-modal-ajax.index');
        Route::post('/cart-modal', [CartModalAjaxController::class, 'store'])->name('cart-modal-ajax.store');
        Route::patch('/cart-modal/{cart_id}', [CartModalAjaxController::class, 'update'])->name('cart-modal-ajax.update');
        Route::delete('/cart-modal/{cart_id}', [CartModalAjaxController::class, 'delete'])->name('cart-modal-ajax.delete');

        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
        Route::patch('/cart/{cart_id}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{cart_id}', [CartController::class, 'delete'])->name('cart.delete');

        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::get('/checkout/cities', [CheckoutController::class, 'cities'])->name('checkout.cities');
        Route::post('/checkout/branches', [CheckoutController::class, 'branches'])->name('checkout.branches');
        Route::post('/checkout/selection', [CheckoutController::class, 'storeSelection'])->name('checkout.selection.store');

        Route::post('/order-confirm', [OrderConfirmController::class, 'storeFastOrder'])->name('order-confirm.store');
        Route::post('/order-validate', [OrderConfirmController::class, 'validateFastOrder'])->name('order-confirm.validate');
        Route::post('/order-confirm/simple', [OrderConfirmController::class, 'storeSimpleOrder'])->name('order-confirm.simple.store');
        Route::post('/order-validate/simple', [OrderConfirmController::class, 'validateSimpleOrder'])->name('order-confirm.simple.validate');

        Route::get('/thank-you/{order_number}', [ThankYouController::class, 'index'])
            ->where('order_number', '[0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{26}')
            ->name('thank-you.index');

        Route::get('/failure', [FailureOrderController::class, 'index'])->name('failure-order.index');
        Route::post('/failure/retry', [FailureOrderController::class, 'retry'])->name('failure-order.retry');
        Route::post('/failure/payment', [FailureOrderController::class, 'payment'])->name('failure-order.payment');

        Route::post('/contacts/contact', [ContactsController::class, 'submitStatic'])->name('contacts.static.submit');
        Route::get('/contacts', [ContactsController::class, 'showStatic'])->name('contacts.static.show');
        Route::post('/{slug}/contact', [ContactsController::class, 'submit'])->name('contacts.submit');
        Route::get('/{slug}', [ContactsController::class, 'show'])->name('contacts.show');
    });
