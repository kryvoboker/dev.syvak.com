<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Http\Controllers\Pages\CheckoutController;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutSelectionStateService;
use App\Services\FooterService;
use App\Services\HeaderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\NovaPoshta\Services\Storefront\NovaPoshtaCheckoutDataService;
use Modules\UkrPoshta\Services\Storefront\UkrPoshtaCheckoutDataService;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['request']->setLaravelSession($this->app['session']->driver());

        Schema::create('global_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('module_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('nwidart_name');
            $table->text('module_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_installed')->default(true);
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_enabled_in_filesystem')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('settings_schema')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });
    }

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
        $this->mock(NovaPoshtaCheckoutDataService::class)
            ->shouldReceive('getCheckoutData')
            ->andReturn([
                'state' => [],
                'selected_city' => [],
                'selected_delivery_point' => [],
            ]);
        $this->mock(UkrPoshtaCheckoutDataService::class)
            ->shouldReceive('getCheckoutData')
            ->andReturn([
                'state' => [],
                'selected_city' => [],
                'selected_delivery_point' => [],
            ]);
        $this->app->instance(CartService::class, new class () {
            public function getTotalProducts(): int
            {
                return 3;
            }

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
        $this->assertSame('storefront.pages.checkout', $response->name());

        /** @var array<string, mixed> $view_data */
        $view_data = $response->getData();

        $this->assertSame('checkout', $view_data['page_type']);
        $this->assertCount(2, $view_data['checkout_data']['visible_items']);
        $this->assertCount(1, $view_data['checkout_data']['hidden_items']);
        $this->assertSame(localized_route('localized.catalog.cart.index'), $view_data['checkout_data']['edit_items_url']);
    }

    public function test_checkout_selection_persists_customer_form_data(): void
    {
        $request = Request::create('/en/checkout/selection', 'POST');
        $request->setLaravelSession($this->app->make('session.store'));
        $state_service = new CheckoutSelectionStateService($request);

        $state = $state_service->replaceState([
            'first_name' => 'Lesya',
            'last_name' => 'Ukrainka',
            'phone' => '+380501234567',
            'email' => 'lesya@example.com',
            'comment' => 'Please call before delivery.',
            'promo_code' => 'WELCOME10',
            'no_call' => '1',
            'delivery_method' => 'nova_poshta',
            'delivery_point' => [
                'id' => 'branch-123',
                'postcode' => '01001',
                'description' => 'Nova Poshta branch 1',
                'branch_value' => 'branch-123',
            ],
            'delivery_address' => 'Street 1, building 2',
        ]);

        $this->assertSame('Lesya', $state['first_name']);
        $this->assertSame('Ukrainka', $state['last_name']);
        $this->assertSame('+380501234567', $state['phone']);
        $this->assertSame('lesya@example.com', $state['email']);
        $this->assertSame('Please call before delivery.', $state['comment']);
        $this->assertSame('WELCOME10', $state['promo_code']);
        $this->assertTrue($state['no_call']);
        $this->assertSame('branch-123', $state['delivery_point']['id']);
        $this->assertSame('01001', $state['delivery_point']['postcode']);
        $this->assertSame('Street 1, building 2', $state['delivery_address']);
    }
}
