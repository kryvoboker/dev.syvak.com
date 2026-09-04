<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Http\Controllers\Pages\ThankYouController;
use App\Models\ApplicationSettings\Currency;
use App\Models\ApplicationSettings\Language;
use App\Models\Orders\OrderCustomers;
use App\Models\Orders\OrderPayments;
use App\Models\Orders\OrderProducts;
use App\Models\Orders\Orders;
use App\Models\Orders\OrderShippings;
use App\Models\Orders\OrderStatuses;
use App\Models\Orders\OrderTotals;
use App\Services\FooterService;
use App\Services\HeaderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Tests\TestCase;

class ThankYouControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        $this->createLanguage();
        $this->createCurrency();
        config()->set('app.currency.current_currency_code', 'UAH');

        $this->app->instance(HeaderService::class, new class () extends HeaderService {
            /**
             * @return array<string, mixed>
             */
            public function __invoke(array $params = []): array
            {
                unset($params);

                return ['categories' => []];
            }
        });
        $this->app->instance(FooterService::class, new class () extends FooterService {
            /**
             * @return array<string, mixed>
             */
            public function __invoke(array $params = []): array
            {
                unset($params);

                return [];
            }
        });
    }

    public function testThankYouPageDisplaysTheCurrentOrderData(): void
    {
        config()->set('devices.current_device_type', config('devices.types.desktop'));

        $order = $this->createOrder();
        OrderCustomers::query()->create([
            'order_id' => $order->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'telephone' => '+380501112233',
        ]);
        OrderShippings::query()->create([
            'order_id' => $order->id,
            'method' => 'Nova Poshta',
            'code' => 'custom_delivery',
            'city' => 'Kyiv',
            'address' => 'Street 1',
            'delivery_point' => 'Branch 10',
        ]);
        OrderPayments::query()->create([
            'order_id' => $order->id,
            'method' => 'Cash on delivery',
            'code' => 'cash_on_delivery',
        ]);
        OrderProducts::query()->create([
            'order_id' => $order->id,
            'name' => 'Long product name',
            'sku' => 'SKU-001',
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
            'is_default_variant' => true,
        ]);
        OrderTotals::query()->create([
            'order_id' => $order->id,
            'total_type' => 'sub_total',
            'value' => 100,
            'sort_order' => 1,
        ]);
        OrderTotals::query()->create([
            'order_id' => $order->id,
            'total_type' => 'promo_code',
            'value' => -25,
            'sort_order' => 2,
        ]);

        $view = app(ThankYouController::class)->index(
            app(HeaderService::class),
            app(FooterService::class),
            app(\App\Services\Order\ThankYouOrderDataService::class),
            'en',
            $order->order_number,
        );

        $this->assertInstanceOf(View::class, $view);
        $data = $view->getData();
        $thank_you_data = $data['thank_you_data'];

        $this->assertTrue($data['order_found']);
        $this->assertSame($order->order_number, $thank_you_data['order_number']);
        $this->assertSame('Jane Doe', $thank_you_data['customer']['name']);
        $this->assertSame('Kyiv, Street 1, Branch 10', $thank_you_data['delivery']['address']);
        $this->assertSame('Long product name', $thank_you_data['products'][0]['name']);
        $this->assertSame('UAH 25.00', $thank_you_data['summary']['promo_code_discount']);
        $this->assertSame('images/thank-you/pc-bg-1.png', $data['background_image_data']['path']);
    }

    public function testThankYouPageDisplaysNotFoundStateForUnknownOrder(): void
    {
        $order_number = '01JTHANKYOUNOTFOUND0000000000';

        $view = app(ThankYouController::class)->index(
            app(HeaderService::class),
            app(FooterService::class),
            app(\App\Services\Order\ThankYouOrderDataService::class),
            'en',
            $order_number,
        );

        $data = $view->getData();

        $this->assertFalse($data['order_found']);
        $this->assertNull($data['thank_you_data']);
        $this->assertSame($order_number, $data['requested_order_number']);
        $this->assertSame('images/thank-you/pc-bg-1.png', $data['background_image_data']['path']);
    }

    private function createLanguage(): void
    {
        Language::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    private function createCurrency(): void
    {
        Currency::query()->create([
            'code' => 'UAH',
            'name' => 'Hryvnia',
            'format_locale' => 'uk_UA',
            'decimal_places' => 2,
            'exchange_rate' => 1,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    private function createOrder(): Orders
    {
        $order_status = OrderStatuses::query()->create([
            'code' => 'new',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return Orders::query()->create([
            'order_number' => '01JTHANKYOUORDER000000000001',
            'order_status_id' => $order_status->id,
            'order_status_name' => 'New',
            'order_type' => 'regular',
            'total' => 100,
            'language_code' => 'en',
            'currency_code' => 'UAH',
            'exchange_rate' => 1,
            'ip' => '127.0.0.1',
            'added_at' => now(),
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_active');
            $table->boolean('is_default');
            $table->timestamps();
        });
        Schema::create('order_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('order_status_id');
            $table->string('order_status_name');
            $table->string('order_type');
            $table->text('comment')->nullable();
            $table->decimal('total', 15, 4);
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('language_code');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('currency_code');
            $table->decimal('exchange_rate', 15, 8);
            $table->string('ip');
            $table->timestamp('added_at');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('format_locale')->nullable();
            $table->string('symbol_left')->nullable();
            $table->string('symbol_right')->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('order_customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->boolean('no_call')->default(false);
            $table->timestamps();
        });
        Schema::create('order_shippings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('method')->nullable();
            $table->string('code')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('delivery_point')->nullable();
            $table->timestamps();
        });
        Schema::create('order_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('method')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });
        Schema::create('order_products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->boolean('is_default_variant');
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();
        });
        Schema::create('order_totals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('total_type');
            $table->string('name')->nullable();
            $table->decimal('value', 15, 4);
            $table->integer('sort_order');
            $table->timestamps();
        });
    }
}
