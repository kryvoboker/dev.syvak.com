<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Tests\Feature;

use Modules\UkrPoshta\Filament\Pages\UkrPoshtaSyncPage;
use Modules\UkrPoshta\Support\UkrPoshtaConfig;
use Modules\UkrPoshta\Tests\TestCase;

class UkrPoshtaConfigTest extends TestCase
{
    public function test_it_reads_and_formats_global_configs(): void
    {
        set_global_config([
            UkrPoshtaConfig::API_KEY_GLOBAL_CONFIG_KEY => ['value' => 'up-test-key', 'is_active' => true],
            UkrPoshtaConfig::DELIVERY_COST_GLOBAL_CONFIG_KEY => ['value' => '149.9', 'is_active' => true],
            UkrPoshtaConfig::IS_DELIVERY_COST_ENABLED_GLOBAL_CONFIG_KEY => ['value' => true, 'is_active' => true],
        ]);

        $config = $this->app->make(UkrPoshtaConfig::class);

        $this->assertSame('up-test-key', $config->getApiKey());
        $this->assertSame('149.90', $config->getDeliveryCost());
        $this->assertTrue($config->isDeliveryCostEnabled());
    }

    public function test_sync_page_saves_global_configs(): void
    {
        $page = $this->app->make(UkrPoshtaSyncPage::class);
        $page->settings_form = [
            'api_key' => 'up-test-key',
            'delivery_cost' => '149.90',
            'is_delivery_cost_enabled' => true,
        ];

        $page->saveSettings();

        $this->assertSame('up-test-key', (string) get_global_config(UkrPoshtaConfig::API_KEY_GLOBAL_CONFIG_KEY));
        $this->assertSame('149.90', (string) get_global_config(UkrPoshtaConfig::DELIVERY_COST_GLOBAL_CONFIG_KEY));
        $this->assertSame('1', (string) get_global_config(UkrPoshtaConfig::IS_DELIVERY_COST_ENABLED_GLOBAL_CONFIG_KEY));
    }

    public function test_sync_page_route_is_registered(): void
    {
        $url = UkrPoshtaSyncPage::getUrl();

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('modules/ukr-poshta', $url);
    }
}
