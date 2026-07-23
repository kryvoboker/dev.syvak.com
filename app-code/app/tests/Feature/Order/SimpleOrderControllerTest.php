<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Cart\CartRequestKeyEnum;
use App\Enums\Order\DeliveryMethodEnum;
use App\Enums\Order\PaymentMethodEnum;
use App\Services\Cart\CartService;
use App\Services\Order\OrderAggregatePersistenceService;
use App\Services\Order\OrderCreationService;
use App\Services\Order\OrderLifecycleService;
use App\Services\Order\Payment\CashOnDeliveryPaymentModule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\BankTransfer\Services\BankTransferPaymentModule;
use Modules\PaymentUponDelivery\Services\PaymentUponDeliveryPaymentModule;
use Modules\Pickup\Services\Storefront\PickupCheckoutDataService;
use Modules\WayForPay\Services\WayForPayPaymentModule;
use Modules\WayForPay\Support\WayForPayConfig;
use Tests\TestCase;

class SimpleOrderControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
        });

        Schema::getConnection()->table('languages')->insert([
            'code' => app()->getLocale(),
            'name' => app()->getLocale(),
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public function testValidateSimpleOrderReturnsSuccessJson(): void
    {
        $locale = app()->getLocale();

        $this->bindSimpleOrderCreationService([
            'success' => true,
            'errors' => [],
            'cart' => [
                'is_empty' => false,
            ],
        ], [
            'success' => false,
            'errors' => [],
        ]);

        $response = $this->postJson(route('localized.catalog.order-confirm.simple.validate', ['locale' => $locale]), $this->validPayload());

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function testStoreSimpleOrderReturnsJsonResult(): void
    {
        $locale = app()->getLocale();
        $order_number = Str::ulid()->toBase32();

        $this->bindSimpleOrderCreationService([
            'success' => true,
            'errors' => [],
            'cart' => [
                'is_empty' => false,
            ],
        ], [
            'success' => true,
            'order_number' => $order_number,
            'redirect_url' => localized_route('localized.catalog.thank-you.index', [
                'locale' => $locale,
                'order_number' => $order_number,
            ]),
            'status' => 'success',
            'errors' => [],
        ]);

        $response = $this->postJson(route('localized.catalog.order-confirm.simple.store', ['locale' => $locale]), $this->validPayload());

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('order_number', $order_number);
    }

    public function testStoreSimpleOrderRedirectsToThankYouPage(): void
    {
        $locale = app()->getLocale();
        $order_number = Str::ulid()->toBase32();
        $redirect_url = localized_route('localized.catalog.thank-you.index', [
            'locale' => $locale,
            'order_number' => $order_number,
        ]);

        $this->bindSimpleOrderCreationService([
            'success' => true,
            'errors' => [],
            'cart' => [
                'is_empty' => false,
            ],
        ], [
            'success' => true,
            'order_number' => $order_number,
            'redirect_url' => $redirect_url,
            'status' => 'success',
            'errors' => [],
        ]);

        $response = $this->post(route('localized.catalog.order-confirm.simple.store', ['locale' => $locale]), $this->validPayload());

        $response->assertRedirect($redirect_url);
    }

    public function testStoreSimpleOrderRequiresDeliveryMethod(): void
    {
        $locale = app()->getLocale();

        $this->bindSimpleOrderCreationService([
            'success' => true,
            'errors' => [],
            'cart' => [
                'is_empty' => false,
            ],
        ], [
            'success' => true,
            'errors' => [],
        ]);

        $payload = $this->validPayload();
        unset($payload['delivery_method']);

        $response = $this->postJson(route('localized.catalog.order-confirm.simple.store', ['locale' => $locale]), $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['delivery_method']);
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'email' => 'john@example.com',
            CartRequestKeyEnum::CartMode->value => CartModeEnum::Regular->value,
            'delivery_method' => DeliveryMethodEnum::PickupStore->value,
            'payment_method' => PaymentMethodEnum::CashOnDelivery->value,
            'delivery_address' => 'Main Street 1',
        ];
    }

    /**
     * @param array<string, mixed> $validate_result
     * @param array<string, mixed> $create_result
     */
    private function bindSimpleOrderCreationService(array $validate_result, array $create_result): void
    {
        $simple_order_service = new SimpleOrderCreationServiceStub($validate_result, $create_result);

        $this->app->instance(OrderCreationService::class, $simple_order_service);
    }
}

/**
 * @internal
 */
final readonly class SimpleOrderCreationServiceStub extends OrderCreationService
{
    /**
     * @param array<string, mixed> $validate_result
     * @param array<string, mixed> $create_result
     */
    public function __construct(
        private array $validate_result,
        private array $create_result,
    ) {
        parent::__construct(
            app(CartService::class),
            app(CashOnDeliveryPaymentModule::class),
            app(PaymentUponDeliveryPaymentModule::class),
            app(BankTransferPaymentModule::class),
            app(WayForPayPaymentModule::class),
            app(WayForPayConfig::class),
            app(OrderAggregatePersistenceService::class),
            app(OrderLifecycleService::class),
            app(PickupCheckoutDataService::class),
        );
    }

    /**
     * @param array<string, mixed> $validated_data
     * @return array<string, mixed>
     */
    public function validateSimpleOrderData(array $validated_data, string $locale): array
    {
        unset($validated_data, $locale);

        return $this->validate_result;
    }

    /**
     * @param array<string, mixed> $validated_data
     * @return array<string, mixed>
     */
    public function createSimpleOrder(array $validated_data, string $locale): array
    {
        unset($validated_data, $locale);

        return $this->create_result;
    }
}
