<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Http\Controllers\Pages\CartController;
use App\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    public function test_cart_page_redirects_to_home_when_cart_is_empty(): void
    {
        $this->app->instance(CartService::class, new class()
        {
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

        $response = app(CartController::class)->index(null);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(localized_route('catalog.home'), $response->getTargetUrl());
    }
}
