<?php

declare(strict_types=1);

namespace Modules\Pickup\Tests\Feature;

use Modules\Pickup\Services\PickupSettingsService;
use Modules\Pickup\Tests\TestCase;

final class PickupSelectionTest extends TestCase
{
    public function test_it_stores_authoritative_localized_address_and_empty_location_state(): void
    {
        $this->createActiveLanguages();
        $this->enablePickupModule();
        $this->app->make(PickupSettingsService::class)->save([
            'uk' => 'м. Київ, вул. Хрещатик, 1',
            'en' => '1 Khreshchatyk Street, Kyiv',
        ], '<iframe src="https://www.google.com/maps/embed?pb=test"></iframe>');

        $response = $this->postJson('/en/checkout/selection', [
            'delivery_method' => 'pickup_store',
            'city' => [
                'city_description' => 'Malicious city',
            ],
            'delivery_point' => [
                'description' => 'Malicious branch',
            ],
            'delivery_address' => 'Malicious address',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('state.delivery_method', 'pickup_store')
            ->assertJsonPath('state.delivery_address', '1 Khreshchatyk Street, Kyiv')
            ->assertJsonPath('state.city', [])
            ->assertJsonPath('state.delivery_point', []);
    }

    public function test_it_rejects_pickup_when_the_module_is_incomplete(): void
    {
        $this->createActiveLanguages();
        $this->enablePickupModule();

        $this->postJson('/en/checkout/selection', [
            'delivery_method' => 'pickup_store',
        ])->assertUnprocessable();
    }
}
