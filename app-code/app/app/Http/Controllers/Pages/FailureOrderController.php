<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pages\FailureOrderRetryRequest;
use App\Models\ApplicationSettings\Language;
use App\Services\Order\FailureOrderRecoveryService;
use App\Services\PageSettings\FailurePageService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FailureOrderController extends Controller
{
    public function index(
        FailurePageService $failure_page_service,
        string $locale,
    ): View {
        $locale = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);
        $page_setting = $failure_page_service->getStaticPageSetting();

        if (! $language instanceof Language || $page_setting === null) {
            throw new NotFoundHttpException();
        }

        $data = $failure_page_service->getViewData($page_setting, (int) $language->id, $locale);

        return view('catalog.pages.failure-order', [
            ...$data,
            'breadcrumbs' => [
                breadcrumb(__('catalog/default.links.home'), localized_route('catalog.home')),
            ],
        ]);
    }

    public function retry(
        FailureOrderRetryRequest $request,
        FailureOrderRecoveryService $recovery_service,
        string $locale,
    ): JsonResponse {
        $locale = normalize_locale($locale);
        $result = $recovery_service->retry((string) $request->validated('payment_method'), $locale);

        return response()->json($result);
    }

    public function payment(
        FailureOrderRetryRequest $request,
        FailureOrderRecoveryService $recovery_service,
        string $locale,
    ): JsonResponse {
        return $this->retry($request, $recovery_service, $locale);
    }
}
