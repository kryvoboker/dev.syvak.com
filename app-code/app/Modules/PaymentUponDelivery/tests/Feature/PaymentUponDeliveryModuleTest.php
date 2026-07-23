<?php

declare(strict_types=1);

namespace Modules\PaymentUponDelivery\Tests\Feature;

use Modules\PaymentUponDelivery\Filament\Pages\PaymentUponDeliverySettingsPage;
use Modules\PaymentUponDelivery\Services\PaymentUponDeliveryPaymentModule;
use Modules\PaymentUponDelivery\Services\Storefront\PaymentUponDeliveryModuleDataService;
use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;
use Modules\PaymentUponDelivery\Tests\TestCase;

final class PaymentUponDeliveryModuleTest extends TestCase
{
    public function test_it_exposes_the_payment_method_only_when_the_singleton_is_enabled(): void
    {
        $payment_data = $this->app->make(PaymentUponDeliveryModuleDataService::class)->getCheckoutData();

        $this->assertFalse($payment_data['is_available']);

        $this->enablePaymentUponDeliveryModule();

        $payment_data = $this->app->make(PaymentUponDeliveryModuleDataService::class)->getCheckoutData();

        $this->assertTrue($payment_data['is_available']);
        $this->assertSame(PaymentUponDeliveryConfig::PAYMENT_METHOD, $payment_data['payment_method']);
    }

    public function test_checkout_selection_stores_the_payment_method(): void
    {
        $this->enablePaymentUponDeliveryModule();

        $this->postJson('/en/checkout/selection', [
            'payment_method' => PaymentUponDeliveryConfig::PAYMENT_METHOD,
        ])
            ->assertOk()
            ->assertJsonPath('state.payment_method', PaymentUponDeliveryConfig::PAYMENT_METHOD);
    }

    public function test_checkout_selection_rejects_an_unavailable_payment_method(): void
    {
        $this->postJson('/en/checkout/selection', [
            'payment_method' => PaymentUponDeliveryConfig::PAYMENT_METHOD,
        ])->assertUnprocessable();
    }

    public function test_the_module_has_an_empty_admin_settings_page_and_localized_translations(): void
    {
        app()->setLocale('en');

        $this->assertStringContainsString('/modules/payment-upon-delivery', PaymentUponDeliverySettingsPage::getUrl());
        $this->assertSame('Payment upon delivery', $this->app->make(PaymentUponDeliverySettingsPage::class)->getTitle());
        $this->assertSame(
            'Payment upon delivery',
            __('paymentupondelivery::admin/modules/payment_upon_delivery.navigation_label'),
        );
    }

    public function test_payment_module_returns_a_pending_payment_intent(): void
    {
        $payment_result = $this->app->make(PaymentUponDeliveryPaymentModule::class)->process([
            'order_number' => 'TMP-TEST',
        ]);

        $this->assertTrue($payment_result['is_success']);
        $this->assertSame('pending', $payment_result['status']);
        $this->assertSame(PaymentUponDeliveryConfig::PAYMENT_METHOD, $payment_result['payload']['payment_method']);
    }
}
