<?php

declare(strict_types=1);

namespace Modules\WayForPay\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\WayForPay\Services\WayForPayOrderPaymentService;
use Modules\WayForPay\Support\WayForPayConfig;
use Throwable;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Handler\ServiceUrlHandler;

final class WayForPayCallbackController
{
    public function __invoke(
        Request $request,
        WayForPayConfig $wayforpay_config,
        WayForPayOrderPaymentService $order_payment_service,
    ): JsonResponse {
        try {
            $settings = $wayforpay_config->getSettings();
            $handler = new ServiceUrlHandler(new AccountSecretCredential(
                $settings['merchant_account'] ?? '',
                $settings['secret_key'] ?? '',
            ));
            $service_response = $handler->parseRequestFromArray($request->all());
            $transaction = $service_response->getTransaction();
            $order = $order_payment_service->applyProviderResponse($request->all());

            Log::channel('stack')->info('[WayForPayCallbackController] payment status received', [
                'order_reference' => $transaction->getOrderReference(),
                'status' => $transaction->getStatus(),
                'order_status' => $order->status?->code,
            ]);

            return response()->json(json_decode(
                (string) $handler->getSuccessResponse($transaction),
                true,
                512,
                JSON_THROW_ON_ERROR,
            ));
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[WayForPayCallbackController] callback validation failed', [
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'status' => 'reject',
            ], 400);
        }
    }
}
