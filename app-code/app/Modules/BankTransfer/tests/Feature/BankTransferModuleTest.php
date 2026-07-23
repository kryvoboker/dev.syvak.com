<?php

declare(strict_types=1);

namespace Modules\BankTransfer\Tests\Feature;

use Modules\BankTransfer\Filament\Pages\BankTransferSettingsPage;
use Modules\BankTransfer\Services\BankTransferPaymentModule;
use Modules\BankTransfer\Services\Filament\BankTransferSettingsService;
use Modules\BankTransfer\Services\Storefront\BankTransferModuleDataService;
use Modules\BankTransfer\Support\BankTransferConfig;
use Modules\BankTransfer\Tests\TestCase;

final class BankTransferModuleTest extends TestCase
{
    public function test_it_exposes_the_payment_method_only_when_the_singleton_is_enabled(): void
    {
        $payment_data = $this->app->make(BankTransferModuleDataService::class)->getCheckoutData('en');

        $this->assertFalse($payment_data['is_available']);

        $this->createActiveLanguages();
        $this->enableBankTransferModule();

        $payment_data = $this->app->make(BankTransferModuleDataService::class)->getCheckoutData('en');

        $this->assertFalse($payment_data['is_available']);

        $this->app->make(BankTransferSettingsService::class)->save([
            'uk' => 'Банківський переказ',
            'en' => 'Bank transfer',
        ], []);

        $payment_data = $this->app->make(BankTransferModuleDataService::class)->getCheckoutData('en');

        $this->assertTrue($payment_data['is_available']);
        $this->assertSame(BankTransferConfig::PAYMENT_METHOD, $payment_data['payment_method']);
        $this->assertSame('Bank transfer', $payment_data['payment_name']);
        $this->assertSame('', $payment_data['payment_information']);
    }

    public function test_it_returns_localized_optional_payment_information(): void
    {
        $this->createActiveLanguages();
        $this->enableBankTransferModule();
        $this->app->make(BankTransferSettingsService::class)->save([
            'uk' => 'Банківський переказ',
            'en' => 'Bank transfer',
        ], [
            'uk' => "Отримувач: Syvak\nIBAN: UA000000000000000000000000000",
            'en' => "Recipient: Syvak\nIBAN: UA000000000000000000000000000",
        ]);

        $payment_data = $this->app->make(BankTransferModuleDataService::class)->getCheckoutData('en');

        $this->assertSame("Recipient: Syvak\nIBAN: UA000000000000000000000000000", $payment_data['payment_information']);
    }

    public function test_checkout_selection_accepts_enabled_and_rejects_disabled_payment_method(): void
    {
        $this->postJson('/en/checkout/selection', [
            'payment_method' => BankTransferConfig::PAYMENT_METHOD,
        ])->assertUnprocessable();

        $this->createActiveLanguages();
        $this->enableBankTransferModule();
        $this->app->make(BankTransferSettingsService::class)->save([
            'uk' => 'Банківський переказ',
            'en' => 'Bank transfer',
        ], []);

        $this->postJson('/en/checkout/selection', [
            'payment_method' => BankTransferConfig::PAYMENT_METHOD,
        ])
            ->assertOk()
            ->assertJsonPath('state.payment_method', BankTransferConfig::PAYMENT_METHOD);
    }

    public function test_it_has_a_localized_admin_settings_page(): void
    {
        app()->setLocale('en');

        $this->assertStringContainsString('/modules/bank-transfer', BankTransferSettingsPage::getUrl());
        $this->assertSame('Bank transfer', $this->app->make(BankTransferSettingsPage::class)->getTitle());
        $this->assertSame(
            'Bank transfer',
            __('banktransfer::admin/modules/bank_transfer.navigation_label'),
        );
    }

    public function test_payment_module_returns_a_pending_bank_transfer_intent(): void
    {
        $payment_result = $this->app->make(BankTransferPaymentModule::class)->process([
            'order_number' => 'TMP-TEST',
        ]);

        $this->assertTrue($payment_result['is_success']);
        $this->assertSame('pending', $payment_result['status']);
        $this->assertSame(BankTransferConfig::PAYMENT_METHOD, $payment_result['provider_code']);
        $this->assertSame(BankTransferConfig::PAYMENT_METHOD, $payment_result['payload']['payment_method']);
    }
}
