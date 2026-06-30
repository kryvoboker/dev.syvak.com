<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Http\Controllers\Pages\CheckoutController;
use App\Services\Cart\CartService;
use App\Services\FooterService;
use App\Services\HeaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    public function test_checkout_page_redirects_to_home_when_cart_is_empty(): void
    {
        $this->app->instance(CartService::class, new class () {
            /**
             * @return array<string, mixed>
             */
            public function getSnapshot(): array
            {
                return [
                    'is_empty' => true,
                ];
            }
        });

        $this->app->instance(HeaderService::class, new class () {
            /**
             * @return array<string, mixed>
             */
            public function __invoke(): array
            {
                return [
                    'categories' => [],
                ];
            }
        });

        $this->app->instance(FooterService::class, new class () {
            /**
             * @return array<string, mixed>
             */
            public function __invoke(): array
            {
                return [];
            }
        });

        $response = app(CheckoutController::class)->index(null);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(localized_route('catalog.home'), $response->getTargetUrl());
    }

    public function test_checkout_page_splits_visible_and_hidden_items(): void
    {
        $this->app->instance(CartService::class, new class () {
            /**
             * @return array<string, mixed>
             */
            public function getSnapshot(): array
            {
                return [
                    'is_empty' => false,
                    'items' => [
                        [
                            'name' => 'Item 01',
                            'url' => '#',
                            'sku' => 'SKU-1',
                            'line_total_formatted' => '100 UAH',
                            'unit_price_formatted' => '100 UAH',
                            'selected_attributes' => [],
                            'image_data' => [],
                        ],
                        [
                            'name' => 'Item 02',
                            'url' => '#',
                            'sku' => 'SKU-2',
                            'line_total_formatted' => '200 UAH',
                            'unit_price_formatted' => '200 UAH',
                            'selected_attributes' => [],
                            'image_data' => [],
                        ],
                        [
                            'name' => 'Item 03',
                            'url' => '#',
                            'sku' => 'SKU-3',
                            'line_total_formatted' => '300 UAH',
                            'unit_price_formatted' => '300 UAH',
                            'selected_attributes' => [],
                            'image_data' => [],
                        ],
                    ],
                    'totals' => [
                        'grand_total_formatted' => '600 UAH',
                    ],
                ];
            }
        });

        $this->app->instance(HeaderService::class, new class () {
            /**
             * @return array<string, mixed>
             */
            public function __invoke(): array
            {
                return [
                    'categories' => [],
                ];
            }
        });

        $this->app->instance(FooterService::class, new class () {
            /**
             * @return array<string, mixed>
             */
            public function __invoke(): array
            {
                return [];
            }
        });

        $response = app(CheckoutController::class)->index(null);

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('catalog.pages.checkout', $response->name());

        /** @var array<string, mixed> $view_data */
        $view_data = $response->getData();

        $this->assertSame('checkout', $view_data['page_type']);
        $this->assertCount(2, $view_data['checkout_data']['visible_items']);
        $this->assertCount(1, $view_data['checkout_data']['hidden_items']);
        $this->assertSame(localized_route('localized.catalog.cart.index'), $view_data['checkout_data']['edit_items_url']);
    }
}
