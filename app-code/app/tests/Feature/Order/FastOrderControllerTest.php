<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Cart\CartRequestKeyEnum;
use App\Models\ApplicationSettings\Language;
use App\Services\Cart\CartService;
use App\Services\Order\OrderAggregatePersistenceService;
use App\Services\Order\OrderCreationService;
use App\Services\Order\OrderLifecycleService;
use App\Services\Order\Payment\CashOnDeliveryPaymentModule;
use Illuminate\Database\Eloquent\Collection;
use Modules\BankTransfer\Services\BankTransferPaymentModule;
use Modules\PaymentUponDelivery\Services\PaymentUponDeliveryPaymentModule;
use Modules\Pickup\Services\Storefront\PickupCheckoutDataService;
use Modules\WayForPay\Services\WayForPayPaymentModule;
use Modules\WayForPay\Support\WayForPayConfig;
use Tests\TestCase;

class FastOrderControllerTest extends TestCase
{
    public function testValidateFastOrderReturnsSuccessJson(): void
    {
        $locale = app()->getLocale();

        $this->bindFastOrderCreationService(
            [
                'success' => true,
                'errors' => [],
                'cart' => [
                    'is_empty' => false,
                ],
            ],
            [
                'success' => false,
                'errors' => [],
                'redirect_url' => localized_route('localized.catalog.thank-you.index', [
                    'locale' => $locale,
                    'order_number' => 'TMP-20260101010101-ABCDEF',
                ]),
            ],
        );

        $response = $this->postJson(route('localized.catalog.order-confirm.validate', ['locale' => $locale]), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            CartRequestKeyEnum::CartMode->value => CartModeEnum::FastOrder->value,
            'payment_method' => 'cash_on_delivery',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function testStoreFastOrderRedirectsToThankYouPage(): void
    {
        $locale = app()->getLocale();

        $this->bindFastOrderCreationService(
            [
                'success' => true,
                'errors' => [],
                'cart' => [
                    'is_empty' => false,
                ],
            ],
            [
                'success' => true,
                'order_number' => 'TMP-20260101010101-ABCDEF',
                'redirect_url' => localized_route('localized.catalog.thank-you.index', [
                    'locale' => $locale,
                    'order_number' => 'TMP-20260101010101-ABCDEF',
                ]),
                'status' => 'success',
                'errors' => [],
            ],
        );

        $response = $this->post(route('localized.catalog.order-confirm.store', ['locale' => $locale]), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            CartRequestKeyEnum::CartMode->value => CartModeEnum::FastOrder->value,
            'payment_method' => 'cash_on_delivery',
        ]);

        $response->assertRedirect(localized_route('localized.catalog.thank-you.index', [
            'locale' => $locale,
            'order_number' => 'TMP-20260101010101-ABCDEF',
        ]));
    }

    public function testStoreFastOrderRequiresFirstName(): void
    {
        $locale = app()->getLocale();

        $this->bindFastOrderCreationService(
            [
                'success' => true,
                'errors' => [],
                'cart' => [
                    'is_empty' => false,
                ],
            ],
            [
                'success' => true,
                'order_number' => 'TMP-20260101010101-ABCDEF',
                'redirect_url' => localized_route('localized.catalog.thank-you.index', [
                    'locale' => $locale,
                    'order_number' => 'TMP-20260101010101-ABCDEF',
                ]),
                'status' => 'success',
                'errors' => [],
            ],
        );

        $response = $this->postJson(route('localized.catalog.order-confirm.store', ['locale' => $locale]), [
            'last_name' => 'Doe',
            'phone' => '1234567890',
            CartRequestKeyEnum::CartMode->value => CartModeEnum::FastOrder->value,
            'payment_method' => 'cash_on_delivery',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['first_name']);
    }

    private function bindFastOrderCreationService(array $validateResult, array $createResult): void
    {
        $this->app->instance(Language::class, new class () extends Language {
            public function getLanguageByCode(string $code): Language
            {
                $language = new Language();
                $language->setAttribute('code', $code);

                return $language;
            }

            public function getActiveLanguages(): Collection
            {
                $language = new Language();
                $language->setAttribute('code', app()->getLocale());

                return new Collection([$language]);
            }
        });

        $fastOrderService = new FastOrderCreationServiceStub($validateResult, $createResult);

        $this->app->instance(OrderCreationService::class, $fastOrderService);
    }
}

/**
 * @internal
 */
final readonly class FastOrderCreationServiceStub extends OrderCreationService
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
    public function validateFastOrderData(array $validated_data, string $locale): array
    {
        unset($validated_data, $locale);

        return $this->validate_result;
    }

    /**
     * @param array<string, mixed> $validated_data
     * @return array<string, mixed>
     */
    public function createFastOrder(array $validated_data, string $locale): array
    {
        unset($validated_data, $locale);

        return $this->create_result;
    }
}
