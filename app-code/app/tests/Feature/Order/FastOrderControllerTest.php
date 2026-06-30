<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Enums\CartModeEnum;
use App\Enums\CartRequestKeyEnum;
use App\Models\ApplicationSettings\Language;
use App\Services\Order\OrderCreationService;
use Illuminate\Database\Eloquent\Collection;
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
                'redirect_url' => localized_route('localized.catalog.thank-you.index', ['locale' => $locale]),
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

        $response->assertRedirect(localized_route('localized.catalog.thank-you.index', ['locale' => $locale]));
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
                'redirect_url' => localized_route('localized.catalog.thank-you.index', ['locale' => $locale]),
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
            public function getActiveLanguages(): Collection
            {
                $language = new Language();
                $language->setAttribute('code', app()->getLocale());

                return new Collection([$language]);
            }
        });

        $fastOrderService = new class ($validateResult, $createResult) {
            /**
             * @param  array<string, mixed>  $validateResult
             * @param  array<string, mixed>  $createResult
             */
            public function __construct(
                private array $validateResult,
                private array $createResult,
            ) {
            }

            /**
             * @param  array<string, mixed>  $validatedData
             * @return array<string, mixed>
             */
            public function validateFastOrderData(array $validatedData, string $locale): array
            {
                unset($validatedData, $locale);

                return $this->validateResult;
            }

            /**
             * @param  array<string, mixed>  $validatedData
             * @return array<string, mixed>
             */
            public function createFastOrder(array $validatedData, string $locale): array
            {
                unset($validatedData, $locale);

                return $this->createResult;
            }
        };

        $this->app->instance(OrderCreationService::class, $fastOrderService);
    }
}
