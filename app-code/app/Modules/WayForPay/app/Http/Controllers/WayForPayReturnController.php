<?php

declare(strict_types=1);

namespace Modules\WayForPay\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\WayForPay\Services\WayForPayOrderPaymentService;
use Modules\WayForPay\Support\WayForPayConfig;
use Throwable;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Handler\ServiceUrlHandler;

final class WayForPayReturnController
{
    public function __invoke(
        string $locale,
        Request $request,
        WayForPayConfig $wayforpay_config,
        WayForPayOrderPaymentService $order_payment_service,
    ): RedirectResponse {
        try {
            $settings = $wayforpay_config->getSettings();
            $handler = new ServiceUrlHandler(new AccountSecretCredential(
                $settings['merchant_account'] ?? '',
                $settings['secret_key'] ?? '',
            ));

            $request_data = $request->all();
            $transaction_status = (string)($request_data['transactionStatus'] ?? '');
            $merchant_signature = trim((string)($request_data['merchantSignature'] ?? ''));

            if ($transaction_status !== 'Approved' && $merchant_signature === '') {
                Log::channel('stack')->error('[WayForPayReturnController] incomplete declined response received', [
                    'status' => $transaction_status,
                    'reason' => $request_data['reason'] ?? null,
                    'reason_code' => $request_data['reasonCode'] ?? null,
                ]);

                return redirect()->to(localized_route('localized.catalog.failure-order.index', ['locale' => $locale]));
            }

            $service_response = $handler->parseRequestFromArray($request_data);
            $transaction = $service_response->getTransaction();
            $reason = $service_response->getReason();
            $order = $order_payment_service->applyProviderResponse($request_data);

            $latest_payment = $order->payments->sortByDesc('id')->first();

            if ($latest_payment?->paymentStatus?->code === 'paid') {
                return redirect()->to(localized_route('localized.catalog.thank-you.index', ['locale' => $locale]));
            }

            Log::channel('stack')->error('[WayForPayReturnController] payment return was not approved', [
                'order_reference' => $transaction->getOrderReference(),
                'status' => $transaction->getStatus(),
                'reason_code' => $reason->getCode(),
                'order_status' => $order->status?->code,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[WayForPayReturnController] payment return validation failed', [
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
                'request_keys' => array_keys($request->all()),
            ]);
        }

        return redirect()->to(localized_route('localized.catalog.failure-order.index', ['locale' => $locale]));
    }
}
