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
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\BankTransfer\Services\Storefront\BankTransferModuleDataService;
use Modules\NovaPoshta\Services\Storefront\NovaPoshtaCheckoutDataService;
use Modules\NovaPoshta\Support\NovaPoshtaCheckoutStateService;
use Modules\PaymentUponDelivery\Services\Storefront\PaymentUponDeliveryModuleDataService;
use Modules\Pickup\Services\Storefront\PickupCheckoutDataService;
use Modules\Pickup\Support\PickupConfig;
use Modules\UkrPoshta\Services\Storefront\UkrPoshtaCheckoutDataService;
use Modules\UkrPoshta\Support\UkrPoshtaCheckoutStateService;
use Modules\WayForPay\Services\Storefront\WayForPayModuleDataService;
use Modules\WayForPay\Support\WayForPayConfig;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutSelectionStateService $checkout_selection_state_service,
        private readonly NovaPoshtaCheckoutDataService $nova_poshta_checkout_data_service,
        private readonly UkrPoshtaCheckoutDataService $ukr_poshta_checkout_data_service,
        private readonly PickupCheckoutDataService $pickup_checkout_data_service,
        private readonly PaymentUponDeliveryModuleDataService $payment_upon_delivery_module_data_service,
        private readonly BankTransferModuleDataService $bank_transfer_module_data_service,
        private readonly WayForPayModuleDataService $wayforpay_module_data_service,
        private readonly WayForPayConfig $wayforpay_config,
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
        $cart_service = app(CartService::class);
        $cart_data = $cart_service->getSnapshot($locale);

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
        $pickup_checkout_data = $this->pickup_checkout_data_service->getCheckoutData($locale);
        $payment_upon_delivery_checkout_data = $this->payment_upon_delivery_module_data_service->getCheckoutData();
        $bank_transfer_checkout_data = $this->bank_transfer_module_data_service->getCheckoutData($locale);
        $wayforpay_checkout_data = $this->wayforpay_module_data_service->getCheckoutData($locale);
        $checkout_selection_state = $this->resolveCheckoutSelectionState(
            $nova_poshta_checkout_data,
            $ukr_poshta_checkout_data,
            [
                $payment_upon_delivery_checkout_data,
                $bank_transfer_checkout_data,
                $wayforpay_checkout_data,
            ],
        );

        $data = [
            'header_data' => $header_data,
            'footer_data' => $footer_data,
            'page_type' => 'checkout',
            'cart_data' => $cart_data,
            'total_products' => $cart_service->getTotalProducts(),
            'checkout_data' => [
                'edit_items_url' => localized_route('localized.catalog.cart.index'),
                'items_count' => count($cart_items),
                'visible_items' => $visible_cart_items,
                'hidden_items' => $hidden_cart_items,
                'subtotal_formatted' => (string)($cart_data['totals']['items_subtotal_formatted'] ?? ''),
                'delivery_formatted' => '—',
                'grand_total_formatted' => (string)($cart_data['totals']['grand_total_formatted'] ?? ''),
            ],
            'checkout_selection_state' => $checkout_selection_state,
            'checkout_map_data' => $this->buildCheckoutMapData($checkout_selection_state),
            'checkout_city_search_url' => localized_route('localized.catalog.checkout.cities'),
            'checkout_branch_search_url' => localized_route('localized.catalog.checkout.branches'),
            'checkout_selection_save_url' => localized_route('localized.catalog.checkout.selection.store'),
            'nova_poshta_checkout_data' => $nova_poshta_checkout_data,
            'ukr_poshta_checkout_data' => $ukr_poshta_checkout_data,
            'pickup_checkout_data' => $pickup_checkout_data,
            'payment_upon_delivery_checkout_data' => $payment_upon_delivery_checkout_data,
            'bank_transfer_checkout_data' => $bank_transfer_checkout_data,
            'wayforpay_checkout_data' => $wayforpay_checkout_data,
            'wayforpay_widget_script_url' => $this->wayforpay_config->getWidgetScriptUrl(),
            'wayforpay_payment_method' => $this->wayforpay_config->getPaymentMethod(),
            'wayforpay_redirect_method' => $this->wayforpay_config->getRedirectMethod(),
            'order_validate_url' => localized_route('localized.catalog.order-confirm.simple.validate'),
            'order_store_url' => localized_route('localized.catalog.order-confirm.simple.store'),
        ];

        return view('storefront.pages.checkout', $data);
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

        $data = $checkout_branch_search_service->loadBranches(
            (string)($validated['delivery_method'] ?? ''),
            (array)($validated['city'] ?? []),
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

        if (($validated['delivery_method'] ?? null) === PickupConfig::DELIVERY_METHOD) {
            $pickup_checkout_data = $this->pickup_checkout_data_service->getCheckoutData($locale);
            $validated['city'] = [];
            $validated['delivery_point'] = [];
            $validated['delivery_address'] = (string) $pickup_checkout_data['store_address'];
        }

        $state = $this->checkout_selection_state_service->replaceState($validated);
        $this->syncModuleStates($state);
        $cart_data = app(CartService::class)->getSnapshot($locale);

        return response()->json([
            'success' => true,
            'state' => $state,
            'cart' => $cart_data,
        ]);
    }

    /**
     * @param array<string, mixed>             $nova_poshta_checkout_data
     * @param array<string, mixed>             $ukr_poshta_checkout_data
     * @param array<int, array<string, mixed>> $payment_checkout_data
     *
     * @return array<string, mixed>
     */
    private function resolveCheckoutSelectionState(
        array $nova_poshta_checkout_data,
        array $ukr_poshta_checkout_data,
        array $payment_checkout_data,
    ): array {
        $current_state = $this->checkout_selection_state_service->getState();

        if ($current_state !== []) {
            $current_payment_method = (string)Arr::get($current_state, 'payment_method', '');

            $current_state['payment_method'] = $current_payment_method !== ''
                ? $current_payment_method
                : $this->resolveDefaultPaymentMethod($payment_checkout_data);

            return $current_state;
        }

        $derived_state = $this->buildSelectionStateFromModuleData(
            $nova_poshta_checkout_data,
            $ukr_poshta_checkout_data,
        );

        $default_payment_method = $this->resolveDefaultPaymentMethod($payment_checkout_data);

        if ($derived_state === []) {
            if ($default_payment_method === '') {
                return [];
            }

            $derived_state = ['payment_method' => $default_payment_method];
        } else {
            $derived_state['payment_method'] = $default_payment_method;
        }

        $this->checkout_selection_state_service->replaceState($derived_state);

        return $derived_state;
    }

    /**
     * @param array<int, array<string, mixed>> $payment_checkout_data
     */
    private function resolveDefaultPaymentMethod(array $payment_checkout_data): string
    {
        foreach ($payment_checkout_data as $payment_data) {
            if (($payment_data['is_available'] ?? false) === true) {
                return (string)($payment_data['payment_method'] ?? '');
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $nova_poshta_checkout_data
     * @param array<string, mixed> $ukr_poshta_checkout_data
     *
     * @return array<string, mixed>
     */
    private function buildSelectionStateFromModuleData(
        array $nova_poshta_checkout_data,
        array $ukr_poshta_checkout_data,
    ): array {
        $nova_state = (array)Arr::get($nova_poshta_checkout_data, 'state', []);
        $nova_selected_city = (array)Arr::get($nova_poshta_checkout_data, 'selected_city', []);
        $nova_selected_delivery_point = (array)Arr::get($nova_poshta_checkout_data, 'selected_delivery_point', []);
        $ukr_selected_city = (array)Arr::get($ukr_poshta_checkout_data, 'selected_city', []);
        $ukr_selected_delivery_point = (array)Arr::get($ukr_poshta_checkout_data, 'selected_delivery_point', []);
        $nova_delivery_method = (string)Arr::get($nova_state, 'delivery_method', '');
        $nova_delivery_address = (string)Arr::get($nova_state, 'delivery_address', '');

        if ($nova_selected_city !== [] || $nova_selected_delivery_point !== []) {
            return [
                'delivery_method' => $this->resolveNovaPoshtaDeliveryMethod(
                    $nova_delivery_method,
                    $nova_selected_delivery_point,
                ),
                'city' => $this->normalizeCheckoutCityState(
                    city_description   : (string)Arr::get($nova_selected_city, 'description', Arr::get($nova_selected_city, 'city_name', '')),
                    nova_poshta_city_id: (string)Arr::get($nova_selected_city, 'ref', ''),
                    ukr_poshta_city_id : null,
                    city_lat           : Arr::get($nova_selected_city, 'latitude'),
                    city_lng           : Arr::get($nova_selected_city, 'longitude'),
                ),
                'delivery_point' => $nova_selected_delivery_point,
                'delivery_address' => $nova_delivery_method === 'nova_poshta_courier' ? $nova_delivery_address : '',
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
                'delivery_address' => '',
            ];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $checkout_selection_state
     *
     * @return array<string, mixed>
     */
    private function buildCheckoutMapData(array $checkout_selection_state): array
    {
        $selected_city = (array)Arr::get($checkout_selection_state, 'city', []);

        return [
            'selected_city' => [
                'city_description' => (string)Arr::get($selected_city, 'city_description', ''),
                'nova_poshta_city_id' => (string)Arr::get($selected_city, 'nova_poshta_city_id', ''),
                'ukr_poshta_city_id' => (int)Arr::get($selected_city, 'ukr_poshta_city_id', 0),
                'city_lat' => Arr::get($selected_city, 'city_lat'),
                'city_lng' => Arr::get($selected_city, 'city_lng'),
            ],
            'selected_delivery_method' => (string)Arr::get($checkout_selection_state, 'delivery_method', ''),
            'marker_icons' => [
                'nova_poshta' => asset('storage/images/icons/nova-poshta-marker.svg'),
                'ukr_poshta' => asset('storage/images/icons/ukr-poshta-marker.svg'),
            ],
            'texts' => [
                'title' => __('storefront/pages/checkout.map.title'),
                'search_placeholder' => __('storefront/pages/checkout.map.search_placeholder'),
                'list_title' => __('storefront/pages/checkout.map.list_title'),
                'empty' => __('storefront/pages/checkout.map.empty'),
                'choose_city_first' => __('storefront/pages/checkout.warnings.choose_city_first'),
                'deliver_here' => __('storefront/pages/checkout.map.deliver_here'),
                'close' => __('storefront/pages/checkout.map.close'),
                'work_schedule' => __('storefront/pages/checkout.map.work_schedule'),
                'day_off' => __('storefront/pages/checkout.map.day_off'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $selected_delivery_point
     */
    private function resolveNovaPoshtaDeliveryMethod(string $delivery_method, array $selected_delivery_point): string
    {
        if ($delivery_method === 'nova_poshta_courier') {
            return 'nova_poshta_courier';
        }

        if ($delivery_method === 'nova_poshta_poshtomat' || $this->isPoshtomatDeliveryPoint($selected_delivery_point)) {
            return 'nova_poshta_poshtomat';
        }

        return 'nova_poshta';
    }

    /**
     * @param array<string, mixed> $selected_delivery_point
     */
    private function isPoshtomatDeliveryPoint(array $selected_delivery_point): bool
    {
        if ($selected_delivery_point === []) {
            return false;
        }

        $delivery_method = (string)Arr::get($selected_delivery_point, 'delivery_method', '');

        return $delivery_method === 'nova_poshta_poshtomat'
            || Str::contains(
                Str::lower((string)Arr::get($selected_delivery_point, 'description', '')),
                'поштомат',
            );
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
        $delivery_method = (string)Arr::get($state, 'delivery_method', '');
        $delivery_point = (array)Arr::get($state, 'delivery_point', []);
        $delivery_address = (string)Arr::get($state, 'delivery_address', '');

        if ($city === []) {
            $this->nova_poshta_checkout_state_service->clear();
            $this->ukr_poshta_checkout_state_service->clear();

            return;
        }

        $nova_poshta_city_id = (string)Arr::get($city, 'nova_poshta_city_id', '');
        $ukr_poshta_city_id = (int)Arr::get($city, 'ukr_poshta_city_id', 0);

        if (filled($nova_poshta_city_id) && Str::startsWith($delivery_method, 'nova_poshta')) {
            $this->nova_poshta_checkout_state_service->setCity([
                'ref' => $nova_poshta_city_id,
                'description' => (string)Arr::get($city, 'city_description', ''),
                'city_name' => (string)Arr::get($city, 'city_description', ''),
                'latitude' => Arr::get($city, 'city_lat'),
                'longitude' => Arr::get($city, 'city_lng'),
            ]);
            $this->nova_poshta_checkout_state_service->setDeliveryMethod($delivery_method);

            if ($delivery_method === 'nova_poshta_courier') {
                $this->nova_poshta_checkout_state_service->setDeliveryAddress($delivery_address);
                $this->nova_poshta_checkout_state_service->clearDeliveryPoint();
            } elseif ($delivery_point !== []) {
                $this->nova_poshta_checkout_state_service->setDeliveryPoint($delivery_point);
                $this->nova_poshta_checkout_state_service->clearDeliveryAddress();
            } else {
                $this->nova_poshta_checkout_state_service->clearDeliveryPoint();
                $this->nova_poshta_checkout_state_service->clearDeliveryAddress();
            }
        } else {
            $this->nova_poshta_checkout_state_service->clear();
        }

        if ($ukr_poshta_city_id > 0 && $delivery_method === 'ukr_poshta') {
            $this->ukr_poshta_checkout_state_service->setCity([
                'city_id' => $ukr_poshta_city_id,
                'description' => (string)Arr::get($city, 'city_description', ''),
                'city_ua' => (string)Arr::get($city, 'city_description', ''),
                'latitude' => Arr::get($city, 'city_lat'),
                'longitude' => Arr::get($city, 'city_lng'),
            ]);
            $this->ukr_poshta_checkout_state_service->setDeliveryMethod('ukr_poshta');

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
