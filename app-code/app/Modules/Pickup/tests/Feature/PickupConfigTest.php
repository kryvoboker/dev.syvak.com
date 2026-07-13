<?php

declare(strict_types=1);

namespace Modules\Pickup\Tests\Feature;

use InvalidArgumentException;
use Modules\Pickup\Filament\Pages\PickupSettingsPage;
use Modules\Pickup\Services\PickupSettingsService;
use Modules\Pickup\Support\PickupConfig;
use Modules\Pickup\Tests\TestCase;

final class PickupConfigTest extends TestCase
{
    public function test_it_saves_localized_addresses_and_only_safe_iframe_html(): void
    {
        $this->createActiveLanguages();

        $saved_settings = $this->app->make(PickupSettingsService::class)->save([
            'uk' => 'м. Київ, вул. Хрещатик, 1',
            'en' => '1 Khreshchatyk Street, Kyiv',
        ], '<iframe src="https://www.google.com/maps/embed?pb=test" onload="alert(1)"></iframe>');

        $config = $this->app->make(PickupConfig::class);

        $this->assertSame('м. Київ, вул. Хрещатик, 1', $config->getAddressForLocale('uk'));
        $this->assertSame('1 Khreshchatyk Street, Kyiv', $config->getAddressForLocale('en'));
        $this->assertStringContainsString('https://www.google.com/maps/embed?pb=test', $saved_settings['map_iframe']);
        $this->assertStringNotContainsString('onload', $saved_settings['map_iframe']);
        $this->assertTrue($config->isComplete(['uk', 'en']));
    }

    public function test_it_rejects_non_google_or_non_iframe_html(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->app->make(PickupSettingsService::class)->save([
            'uk' => 'м. Київ, вул. Хрещатик, 1',
            'en' => '1 Khreshchatyk Street, Kyiv',
        ], '<script>alert(1)</script>');
    }

    public function test_map_iframe_is_optional_when_all_localized_addresses_are_configured(): void
    {
        $this->createActiveLanguages();

        $saved_settings = $this->app->make(PickupSettingsService::class)->save([
            'uk' => 'м. Київ, вул. Хрещатик, 1',
            'en' => '1 Khreshchatyk Street, Kyiv',
        ], '');

        $config = $this->app->make(PickupConfig::class);

        $this->assertTrue($config->isComplete(['uk', 'en']));
        $this->assertSame('', $saved_settings['map_iframe']);
        $this->assertSame('', $config->getSafeMapIframe());
    }

    public function test_configuration_is_incomplete_when_an_active_language_is_missing(): void
    {
        $this->createActiveLanguages();

        $this->app->make(PickupSettingsService::class)->save([
            'uk' => 'м. Київ, вул. Хрещатик, 1',
        ], '<iframe src="https://www.google.com/maps/embed?pb=test"></iframe>');

        $this->assertFalse($this->app->make(PickupConfig::class)->isComplete(['uk', 'en']));
    }

    public function test_settings_page_saves_the_singleton_configuration(): void
    {
        $this->createActiveLanguages();
        $page = $this->app->make(PickupSettingsPage::class);
        $page->settings_form = [
            'addresses' => [
                'uk' => 'м. Київ, вул. Хрещатик, 1',
                'en' => '1 Khreshchatyk Street, Kyiv',
            ],
            'map_iframe' => '<iframe src="https://www.google.com/maps/embed?pb=test"></iframe>',
        ];

        $page->saveSettings($this->app->make(PickupSettingsService::class));

        $this->assertSame('1 Khreshchatyk Street, Kyiv', $this->app->make(PickupConfig::class)->getAddressForLocale('en'));
    }

    public function test_settings_page_has_a_registered_admin_route(): void
    {
        $this->assertStringContainsString('/modules/pickup-store', PickupSettingsPage::getUrl());
    }

    public function test_module_translations_are_registered(): void
    {
        app()->setLocale('en');

        $this->assertSame('Pickup from store', __('pickup::admin/modules/pickup.title'));
    }
}
