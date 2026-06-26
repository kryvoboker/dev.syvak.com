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
    public function test_validate_fast_order_returns_success_json(): void
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

    public function test_store_fast_order_redirects_to_thank_you_page(): void
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

    public function test_store_fast_order_requires_first_name(): void
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

    private function bindFastOrderCreationService(array $validate_result, array $create_result): void
    {
        $this->app->instance(Language::class, new class () extends Language {
            public function getActiveLanguages(): Collection
            {
                $language = new Language();
                $language->setAttribute('code', app()->getLocale());

                return new Collection([$language]);
            }
        });

        $fast_order_creation_service = new readonly class ($validate_result, $create_result) extends OrderCreationService {
            /**
             * @param  array<string, mixed>  $validate_result
             * @param  array<string, mixed>  $create_result
             */
            public function __construct(
                private array $validate_result,
                private array $create_result,
            ) {
            }

            /**
             * @param  array<string, mixed>  $validated_data
             * @return array<string, mixed>
             */
            public function validateFastOrderData(array $validated_data, string $locale): array
            {
                return $this->validate_result;
            }

            /**
             * @param  array<string, mixed>  $validated_data
             * @return array<string, mixed>
             */
            public function createFastOrder(array $validated_data, string $locale): array
            {
                return $this->create_result;
            }
        };

        $this->app->instance(OrderCreationService::class, $fast_order_creation_service);
    }
}
