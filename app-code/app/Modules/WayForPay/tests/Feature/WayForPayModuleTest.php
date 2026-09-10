<?php

declare(strict_types=1);

namespace Modules\WayForPay\Tests\Feature;

use Modules\WayForPay\Filament\Pages\WayForPaySettingsPage;
use Modules\WayForPay\Services\Filament\WayForPaySettingsService;
use Modules\WayForPay\Services\Storefront\WayForPayModuleDataService;
use Modules\WayForPay\Services\WayForPayPaymentModule;
use Modules\WayForPay\Tests\TestCase;

final class WayForPayModuleTest extends TestCase
{
    public function test_it_is_unavailable_until_the_singleton_and_all_required_settings_are_configured(): void
    {
        $payment_data = $this->app->make(WayForPayModuleDataService::class)->getCheckoutData('en');

        $this->assertFalse($payment_data['is_available']);

        $this->createActiveLanguages();
        $this->enableWayForPayModule();
        $this->app->make(WayForPaySettingsService::class)->save([
            'merchant_account' => 'merchant-test',
            'secret_key' => 'secret-test',
            'merchant_domain_name' => 'example.test',
        ], [
            'uk' => 'Оплата карткою',
        ]);

        $payment_data = $this->app->make(WayForPayModuleDataService::class)->getCheckoutData('en');

        $this->assertFalse($payment_data['is_available']);

        $this->app->make(WayForPaySettingsService::class)->save([
            'merchant_account' => 'merchant-test',
            'secret_key' => 'secret-test',
            'merchant_domain_name' => 'example.test',
        ], [
            'uk' => 'Оплата карткою',
            'en' => 'Card payment',
        ]);

        $payment_data = $this->app->make(WayForPayModuleDataService::class)->getCheckoutData('en');

        $this->assertTrue($payment_data['is_available']);
        $this->assertSame('wayforpay', $payment_data['payment_method']);
        $this->assertSame('Card payment', $payment_data['payment_name']);
    }

    public function test_checkout_selection_accepts_enabled_and_rejects_disabled_payment_method(): void
    {
        $this->postJson('/en/checkout/selection', [
            'payment_method' => 'wayforpay',
        ])->assertUnprocessable();

        $this->createActiveLanguages();
        $this->enableWayForPayModule();
        $this->app->make(WayForPaySettingsService::class)->save([
            'merchant_account' => 'merchant-test',
            'secret_key' => 'secret-test',
            'merchant_domain_name' => 'example.test',
        ], [
            'uk' => 'Оплата карткою',
            'en' => 'Card payment',
        ]);

        $this->postJson('/en/checkout/selection', [
            'payment_method' => 'wayforpay',
        ])
            ->assertOk()
            ->assertJsonPath('state.payment_method', 'wayforpay');
    }

    public function test_payment_module_builds_widget_and_redirect_payloads_with_defaults(): void
    {
        $this->enableWayForPayModule();
        $this->app->make(WayForPaySettingsService::class)->save([
            'merchant_account' => 'merchant-test',
            'secret_key' => 'secret-test',
            'merchant_domain_name' => 'example.test',
            'checkout_widget_enabled' => true,
            'payment_systems' => 'card;googlePay',
        ], []);

        $payment_result = $this->app->make(WayForPayPaymentModule::class)->prepare([
            'order_number' => 'ORD-TEST-001',
            'customer' => [
                'first_name' => 'Lesya',
                'last_name' => 'Ukrainka',
                'email' => 'lesya@example.com',
                'phone' => '+380501234567',
            ],
            'cart' => [
                'items' => [[
                    'name' => 'Test product',
                    'unit_price' => 125.50,
                    'quantity' => 2,
                ]],
                'totals' => [
                    'grand_total' => 251,
                    'currency_code' => 'UAH',
                ],
            ],
            'return_url' => 'https://example.test/en/wayforpay/return',
            'service_url' => 'https://example.test/en/wayforpay/callback',
        ]);

        $this->assertTrue($payment_result['success']);
        $this->assertSame('wayforpay', $payment_result['payment_method']);
        if (! array_key_exists('use_widget', $payment_result) || ! array_key_exists('redirect_data', $payment_result) || ! array_key_exists('widget_data', $payment_result)) {
            self::fail('WayForPay response does not contain payment data.');
        }
        $this->assertTrue($payment_result['use_widget']);
        $this->assertSame('POST', $payment_result['redirect_data']['method']);
        $this->assertSame('https://secure.wayforpay.com/pay', $payment_result['redirect_data']['action']);
        $this->assertSame(1, $payment_result['widget_data']['apiVersion']);
        $this->assertSame('AUTO', $payment_result['widget_data']['merchantTransactionType']);
        $this->assertSame('AUTO', $payment_result['widget_data']['merchantTransactionSecureType']);
        $this->assertSame('SimpleSignature', $payment_result['widget_data']['merchantAuthType']);
        $this->assertSame('UA', $payment_result['widget_data']['language']);
    }

    public function test_it_has_a_localized_admin_settings_page(): void
    {
        app()->setLocale('en');

        $this->assertStringContainsString('/modules/wayforpay', WayForPaySettingsPage::getUrl());
        $this->assertSame('WayForPay', $this->app->make(WayForPaySettingsPage::class)->getTitle());
        $this->assertSame(
            'WayForPay',
            __('wayforpay::admin/modules/wayforpay.navigation_label'),
        );
    }
}
