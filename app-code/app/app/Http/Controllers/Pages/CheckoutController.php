<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pages\CheckoutBranchSearchRequest;
use App\Http\Requests\Pages\CheckoutCitySearchRequest;
use App\Http\Requests\Pages\CheckoutSelectionStoreRequest;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutBranchSearchService;
use App\Services\Checkout\CheckoutCitySearchService;
use App\Services\Checkout\CheckoutSelectionStateService;
use App\Services\FooterService;
use App\Services\HeaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\NovaPoshta\Services\NovaPoshtaCheckoutDataService;
use Modules\NovaPoshta\Support\NovaPoshtaCheckoutStateService;
use Modules\UkrPoshta\Services\UkrPoshtaCheckoutDataService;
use Modules\UkrPoshta\Support\UkrPoshtaCheckoutStateService;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutSelectionStateService $checkout_selection_state_service,
        private readonly NovaPoshtaCheckoutDataService $nova_poshta_checkout_data_service,
        private readonly UkrPoshtaCheckoutDataService $ukr_poshta_checkout_data_service,
        private readonly NovaPoshtaCheckoutStateService $nova_poshta_checkout_state_service,
        private readonly UkrPoshtaCheckoutStateService $ukr_poshta_checkout_state_service,
    ) {
    }

    /**
     * @param string|null $locale
     *
     * @return View|RedirectResponse
     */
    public function index(
        ?string $locale,
    ): View|RedirectResponse {
        $locale = normalize_locale($locale);
        $cart_data = app(CartService::class)->getSnapshot($locale);

        if (($cart_data['is_empty'] ?? true) === true) {
            return redirect()->to(localized_route('localized.catalog.home'));
        }

        $header_data = app(HeaderService::class)();
        $footer_data = app(FooterService::class)([
            'categories' => $header_data['categories'],
        ]);

        $cart_items = array_values(is_array($cart_data['items'] ?? null) ? $cart_data['items'] : []);
        $visible_cart_items = array_slice($cart_items, 0, 2);
        $hidden_cart_items = array_slice($cart_items, 2);

        $nova_poshta_checkout_data = $this->nova_poshta_checkout_data_service->getCheckoutData();
        $ukr_poshta_checkout_data = $this->ukr_poshta_checkout_data_service->getCheckoutData();
        $checkout_selection_state = $this->resolveCheckoutSelectionState(
            $nova_poshta_checkout_data,
            $ukr_poshta_checkout_data,
        );

        return view('catalog.pages.checkout', [
            'header_data' => $header_data,
            'footer_data' => $footer_data,
            'page_type' => 'checkout',
            'cart_data' => $cart_data,
            'checkout_data' => [
                'edit_items_url' => localized_route('localized.catalog.cart.index'),
                'items_count' => count($cart_items),
                'visible_items' => $visible_cart_items,
                'hidden_items' => $hidden_cart_items,
                'subtotal_formatted' => (string)($cart_data['totals']['grand_total_formatted'] ?? ''),
                'delivery_formatted' => '—',
                'grand_total_formatted' => (string)($cart_data['totals']['grand_total_formatted'] ?? ''),
            ],
            'checkout_selection_state' => $checkout_selection_state,
            'checkout_city_search_url' => localized_route('localized.catalog.checkout.cities'),
            'checkout_branch_search_url' => localized_route('localized.catalog.checkout.branches'),
            'checkout_selection_save_url' => localized_route('localized.catalog.checkout.selection.store'),
            'nova_poshta_checkout_data' => $nova_poshta_checkout_data,
            'ukr_poshta_checkout_data' => $ukr_poshta_checkout_data,
        ]);
    }

    /**
     * @param CheckoutCitySearchRequest $request
     * @param CheckoutCitySearchService $checkout_city_search_service
     * @param string|null               $locale
     *
     * @throws Throwable
     * @return JsonResponse
     */
    public function cities(
        CheckoutCitySearchRequest $request,
        CheckoutCitySearchService $checkout_city_search_service,
        ?string $locale,
    ): JsonResponse {
        $locale = normalize_locale($locale);
        $data = $checkout_city_search_service->searchCities((string)$request->validated('city_keyword'));

        return response()->json([
            'success' => $data['success'],
            'locale' => $locale,
            'items' => $data['cities_data'],
        ]);
    }

    public function branches(
        CheckoutBranchSearchRequest $request,
        CheckoutBranchSearchService $checkout_branch_search_service,
        ?string $locale,
    ): JsonResponse {
        $locale = normalize_locale($locale);
        $validated = $request->validated();

        $data = $checkout_branch_search_service->searchBranches(
            (string) ($validated['delivery_method'] ?? ''),
            (array) ($validated['city'] ?? []),
            (string) ($validated['branch_keyword'] ?? ''),
        );

        return response()->json([
            'success' => $data['success'],
            'locale' => $locale,
            'items' => $data['items'],
        ]);
    }

    public function storeSelection(
        CheckoutSelectionStoreRequest $request,
        ?string $locale,
    ): JsonResponse {
        $locale = normalize_locale($locale);
        $validated = $request->validated();
        $state = $this->checkout_selection_state_service->replaceState($validated);
        $this->syncModuleStates($state);

        Log::channel('daily')->info('[CheckoutController.storeSelection] checkout selection saved', [
            'locale' => $locale,
            'has_city' => filled(Arr::get($state, 'city.city_description', '')),
            'delivery_method' => (string)Arr::get($state, 'delivery_method', ''),
        ]);

        return response()->json([
            'success' => true,
            'state' => $state,
        ]);
    }

    /**
     * @param array<string, mixed> $nova_poshta_checkout_data
     * @param array<string, mixed> $ukr_poshta_checkout_data
     *
     * @return array<string, mixed>
     */
    private function resolveCheckoutSelectionState(array $nova_poshta_checkout_data, array $ukr_poshta_checkout_data): array
    {
        $current_state = $this->checkout_selection_state_service->getState();

        if ($current_state !== []) {
            return $current_state;
        }

        $derived_state = $this->buildSelectionStateFromModuleData($nova_poshta_checkout_data, $ukr_poshta_checkout_data);

        if ($derived_state !== []) {
            $this->checkout_selection_state_service->replaceState($derived_state);
        }

        return $derived_state;
    }

    /**
     * @param array<string, mixed> $nova_poshta_checkout_data
     * @param array<string, mixed> $ukr_poshta_checkout_data
     *
     * @return array<string, mixed>
     */
    private function buildSelectionStateFromModuleData(array $nova_poshta_checkout_data, array $ukr_poshta_checkout_data): array
    {
        $nova_selected_city = (array)Arr::get($nova_poshta_checkout_data, 'selected_city', []);
        $nova_selected_delivery_point = (array)Arr::get($nova_poshta_checkout_data, 'selected_delivery_point', []);
        $ukr_selected_city = (array)Arr::get($ukr_poshta_checkout_data, 'selected_city', []);
        $ukr_selected_delivery_point = (array)Arr::get($ukr_poshta_checkout_data, 'selected_delivery_point', []);

        if ($nova_selected_city !== [] || $nova_selected_delivery_point !== []) {
            return [
                'delivery_method' => 'nova_poshta',
                'city' => $this->normalizeCheckoutCityState(
                    city_description   : (string)Arr::get($nova_selected_city, 'description', Arr::get($nova_selected_city, 'city_name', '')),
                    nova_poshta_city_id: (string)Arr::get($nova_selected_city, 'ref', ''),
                    ukr_poshta_city_id : null,
                    city_lat           : Arr::get($nova_selected_city, 'latitude'),
                    city_lng           : Arr::get($nova_selected_city, 'longitude'),
                ),
                'delivery_point' => $nova_selected_delivery_point,
            ];
        }

        if ($ukr_selected_city !== [] || $ukr_selected_delivery_point !== []) {
            return [
                'delivery_method' => 'ukr_poshta',
                'city' => $this->normalizeCheckoutCityState(
                    city_description   : (string)Arr::get($ukr_selected_city, 'description', Arr::get($ukr_selected_city, 'city_ua', '')),
                    nova_poshta_city_id: null,
                    ukr_poshta_city_id : (int)Arr::get($ukr_selected_city, 'city_id', 0),
                    city_lat           : Arr::get($ukr_selected_city, 'latitude'),
                    city_lng           : Arr::get($ukr_selected_city, 'longitude'),
                ),
                'delivery_point' => $ukr_selected_delivery_point,
            ];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCheckoutCityState(
        string $city_description,
        ?string $nova_poshta_city_id,
        ?int $ukr_poshta_city_id,
        mixed $city_lat,
        mixed $city_lng,
    ): array {
        return array_filter([
            'city_description' => $city_description,
            'nova_poshta_city_id' => $nova_poshta_city_id,
            'ukr_poshta_city_id' => $ukr_poshta_city_id,
            'city_lat' => is_numeric($city_lat) ? (float)$city_lat : null,
            'city_lng' => is_numeric($city_lng) ? (float)$city_lng : null,
        ], static fn (mixed $value): bool => !is_null($value) && $value !== '');
    }

    /**
     * @param array<string, mixed> $state
     */
    private function syncModuleStates(array $state): void
    {
        $city = (array)Arr::get($state, 'city', []);

        if ($city === []) {
            $this->nova_poshta_checkout_state_service->clear();
            $this->ukr_poshta_checkout_state_service->clear();

            return;
        }

        $nova_poshta_city_id = (string)Arr::get($city, 'nova_poshta_city_id', '');
        $ukr_poshta_city_id = (int)Arr::get($city, 'ukr_poshta_city_id', 0);
        $delivery_point = (array)Arr::get($state, 'delivery_point', []);

        if (filled($nova_poshta_city_id)) {
            $this->nova_poshta_checkout_state_service->setCity([
                'ref' => $nova_poshta_city_id,
                'description' => (string)Arr::get($city, 'city_description', ''),
                'city_name' => (string)Arr::get($city, 'city_description', ''),
                'latitude' => Arr::get($city, 'city_lat'),
                'longitude' => Arr::get($city, 'city_lng'),
            ]);

            if ($delivery_point !== []) {
                $this->nova_poshta_checkout_state_service->setDeliveryPoint($delivery_point);
            } else {
                $this->nova_poshta_checkout_state_service->clearDeliveryPoint();
            }
        } else {
            $this->nova_poshta_checkout_state_service->clear();
        }

        if ($ukr_poshta_city_id > 0) {
            $this->ukr_poshta_checkout_state_service->setCity([
                'city_id' => $ukr_poshta_city_id,
                'description' => (string)Arr::get($city, 'city_description', ''),
                'city_ua' => (string)Arr::get($city, 'city_description', ''),
                'latitude' => Arr::get($city, 'city_lat'),
                'longitude' => Arr::get($city, 'city_lng'),
            ]);

            if ($delivery_point !== []) {
                $this->ukr_poshta_checkout_state_service->setDeliveryPoint($delivery_point);
            } else {
                $this->ukr_poshta_checkout_state_service->clearDeliveryPoint();
            }
        } else {
            $this->ukr_poshta_checkout_state_service->clear();
        }
    }
}
