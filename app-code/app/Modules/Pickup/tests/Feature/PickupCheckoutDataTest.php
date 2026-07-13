<?php

declare(strict_types=1);

namespace Modules\Pickup\Tests\Feature;

use Modules\Pickup\Services\PickupCheckoutDataService;
use Modules\Pickup\Services\PickupSettingsService;
use Modules\Pickup\Tests\TestCase;

final class PickupCheckoutDataTest extends TestCase
{
    public function test_it_returns_localized_checkout_data_only_when_the_module_is_complete(): void
    {
        $this->createActiveLanguages();
        $this->enablePickupModule();
        $this->app->make(PickupSettingsService::class)->save([
            'uk' => 'м. Київ, вул. Хрещатик, 1',
            'en' => '1 Khreshchatyk Street, Kyiv',
        ], '<iframe src="https://www.google.com/maps/embed?pb=test"></iframe>');

        $checkout_data = $this->app->make(PickupCheckoutDataService::class)->getCheckoutData('en');

        $this->assertTrue($checkout_data['is_available']);
        $this->assertSame('pickup_store', $checkout_data['delivery_method']);
        $this->assertSame('1 Khreshchatyk Street, Kyiv', $checkout_data['store_address']);
        $this->assertStringContainsString('google.com/maps/embed', $checkout_data['map_iframe']);
    }

    public function test_it_hides_incomplete_pickup_configuration(): void
    {
        $this->createActiveLanguages();
        $this->enablePickupModule();

        $checkout_data = $this->app->make(PickupCheckoutDataService::class)->getCheckoutData('uk');

        $this->assertFalse($checkout_data['is_available']);
        $this->assertSame('', $checkout_data['store_address']);
        $this->assertSame('', $checkout_data['map_iframe']);
    }

    public function test_it_returns_pickup_checkout_data_without_an_optional_map(): void
    {
        $this->createActiveLanguages();
        $this->enablePickupModule();
        $this->app->make(PickupSettingsService::class)->save([
            'uk' => 'м. Київ, вул. Хрещатик, 1',
            'en' => '1 Khreshchatyk Street, Kyiv',
        ], '');

        $checkout_data = $this->app->make(PickupCheckoutDataService::class)->getCheckoutData('en');

        $this->assertTrue($checkout_data['is_available']);
        $this->assertSame('1 Khreshchatyk Street, Kyiv', $checkout_data['store_address']);
        $this->assertSame('', $checkout_data['map_iframe']);

        $html = view('pickup::storefront.module', [
            'pickup_checkout_data' => $checkout_data,
        ])->render();

        $this->assertStringNotContainsString('pickup-store-map-collapse', $html);
    }

    public function test_checkout_data_service_returns_pickup_checkout_data(): void
    {
        $this->createActiveLanguages();
        $this->enablePickupModule();
        $this->app->make(PickupSettingsService::class)->save([
            'uk' => 'м. Київ, вул. Хрещатик, 1',
            'en' => '1 Khreshchatyk Street, Kyiv',
        ], '<iframe src="https://www.google.com/maps/embed?pb=test"></iframe>');

        $pickup_checkout_data_service = $this->app->make(PickupCheckoutDataService::class);
        $checkout_data = $pickup_checkout_data_service->getCheckoutData('uk');

        $this->assertTrue($checkout_data['is_available']);
        $this->assertSame('pickup_store', $checkout_data['delivery_method']);
        $this->assertSame('м. Київ, вул. Хрещатик, 1', $checkout_data['store_address']);
        $this->assertStringContainsString('google.com/maps/embed', $checkout_data['map_iframe']);
    }
}
