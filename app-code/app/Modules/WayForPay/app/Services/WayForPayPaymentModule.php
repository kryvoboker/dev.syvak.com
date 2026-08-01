<?php

declare(strict_types=1);

namespace Modules\WayForPay\Services;

use App\Enums\Order\OrderDataKeyEnum;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\WayForPay\Support\WayForPayConfig;
use Throwable;
use WayForPay\SDK\Collection\ProductCollection;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Domain\Client;
use WayForPay\SDK\Domain\PaymentSystems;
use WayForPay\SDK\Domain\Product;
use WayForPay\SDK\Wizard\PurchaseWizard;

final class WayForPayPaymentModule
{
    public function __construct(
        private readonly WayForPayConfig $wayforpay_config,
    ) {
    }

    /**
     * @param array<string, mixed> $order_payload
     * @return array{
     *     success: bool,
     *     payment_method: string,
     *     use_widget?: bool,
     *     widget_data?: array<string, mixed>,
     *     redirect_data?: array{action: string, method: string, fields: array<string, mixed>},
     *     errors: array<string, array<int, mixed>>
     * }
     */
    public function prepare(array $order_payload): array
    {
        try {
            $settings = $this->wayforpay_config->getSettings();
            $cart = (array) Arr::get($order_payload, 'cart', []);
            $products = new ProductCollection($this->buildProducts((array) Arr::get($cart, 'items', [])));
            $credential = new AccountSecretCredential(
                $this->wayforpay_config->get('merchant_account'),
                $this->wayforpay_config->get('secret_key'),
            );
            $order_reference = (string) Arr::get($order_payload, 'order_number');
            $order_date = Carbon::now(config('app.timezone'));
            $payment_form = PurchaseWizard::get($credential)
                ->setOrderReference($order_reference)
                ->setAmount((float) Arr::get($cart, 'totals.grand_total', 0))
                ->setCurrency((string) Arr::get($cart, 'totals.currency_code', config('app.currency.current_currency_code')))
                ->setOrderDate($order_date)
                ->setMerchantDomainName($this->wayforpay_config->get('merchant_domain_name'))
                ->setMerchantTransactionType($settings['merchant_transaction_type'] ?? $this->wayforpay_config->getDefault('merchant_transaction_type'))
                ->setMerchantTransactionSecureType($settings['merchant_transaction_secure_type'] ?? $this->wayforpay_config->getDefault('merchant_transaction_secure_type'))
                ->setClient($this->buildClient((array) Arr::get($order_payload, 'customer', [])))
                ->setProducts($products)
                ->setReturnUrl((string) Arr::get($order_payload, 'return_url'))
                ->setServiceUrl((string) Arr::get($order_payload, 'service_url'))
                ->setLanguage($settings['language'] ?? $this->wayforpay_config->getDefault('language'))
                ->setMerchantAuthType($settings['merchant_auth_type'] ?? $this->wayforpay_config->getDefault('merchant_auth_type'));

            $payment_systems = string_to_array((string) ($settings['payment_systems'] ?? ''), ';');
            $use_widget = $this->wayforpay_config->getBoolean('checkout_widget_enabled', true);

            if ($payment_systems !== []) {
                $payment_form->setPaymentSystems(new PaymentSystems($payment_systems));
            }

            $payment_data = $payment_form->getForm()->getData();
            $payment_data['apiVersion'] = (int) ($settings['api_version'] ?? $this->wayforpay_config->getDefault('api_version'));
            $payment_data['merchantAuthType'] = $settings['merchant_auth_type'] ?? $this->wayforpay_config->getDefault('merchant_auth_type');

            foreach (['holdTimeout', 'orderTimeout', 'orderLifetime'] as $optional_timeout) {
                if (isset($payment_data[$optional_timeout]) && (int) $payment_data[$optional_timeout] <= 0) {
                    unset($payment_data[$optional_timeout]);
                }
            }

            return [
                'success' => true,
                OrderDataKeyEnum::PaymentMethod->value => $this->wayforpay_config->getPaymentMethod(),
                'use_widget' => $use_widget,
                'widget_data' => array_filter($payment_data, static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []),
                'redirect_data' => [
                    'action' => $this->wayforpay_config->getPaymentEndpoint(),
                    'method' => $this->wayforpay_config->getRedirectMethod(),
                    'fields' => array_filter($payment_data, static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []),
                ],
                'errors' => [],
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[WayForPayPaymentModule.prepare] payment preparation failed', [
                'order_reference' => Arr::get($order_payload, 'order_number'),
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return [
                'success' => false,
                OrderDataKeyEnum::PaymentMethod->value => $this->wayforpay_config->getPaymentMethod(),
                'errors' => [
                    'payment' => [__('wayforpay::storefront/checkout.payment.failed')],
                ],
            ];
        }
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, Product>
     */
    private function buildProducts(array $items): array
    {
        /** @var array<int, Product> $products */
        $products = collect($items)
            ->map(function (mixed $item): ?Product {
                $item = (array) $item;
                $name = trim((string) Arr::get($item, 'name', ''));
                $price = (float) Arr::get($item, 'unit_price', 0);
                $quantity = max(1, (int) Arr::get($item, 'quantity', 1));

                return $name !== '' && $price >= 0 ? new Product($name, $price, $quantity) : null;
            })
            ->filter(fn (mixed $product): bool => $product instanceof Product)
            ->values()
            ->all();

        return $products;
    }

    /**
     * @param array<string, mixed> $customer
     */
    private function buildClient(array $customer): Client
    {
        return new Client(
            (string) Arr::get($customer, 'first_name', ''),
            (string) Arr::get($customer, 'last_name', ''),
            (string) Arr::get($customer, 'email', ''),
            (string) Arr::get($customer, 'phone', ''),
            $this->wayforpay_config->getClientCountry(),
        );
    }
}
