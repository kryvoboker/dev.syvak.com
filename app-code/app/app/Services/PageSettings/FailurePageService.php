<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Models\PageSettings\PageSetting;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\Order\FailureOrderRecoveryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\BankTransfer\Services\Storefront\BankTransferModuleDataService;
use Modules\PaymentUponDelivery\Services\Storefront\PaymentUponDeliveryModuleDataService;
use Throwable;

final readonly class FailurePageService
{
    public function __construct(
        private PageSettingsBootstrapService $page_settings_bootstrap_service,
        private ContactsPageService $contacts_page_service,
        private HeaderService $header_service,
        private FooterService $footer_service,
        private PaymentUponDeliveryModuleDataService $payment_upon_delivery_data_service,
        private BankTransferModuleDataService $bank_transfer_data_service,
        private FailureOrderRecoveryService $failure_order_recovery_service,
    ) {
    }

    public function findBySlug(string $slug, int $language_id): ?PageSetting
    {
        return PageSetting::query()
            ->where('page_type', PageSetting::PAGE_TYPE_FAILURE)
            ->whereHas('slugs', function (Builder $query) use ($slug, $language_id): void {
                $query
                    ->where('language_id', $language_id)
                    ->where('slug', $slug);
            })
            ->with('slugs')
            ->first();
    }

    public function getStaticPageSetting(): ?PageSetting
    {
        $page_setting = PageSetting::query()
            ->where('page_type', PageSetting::PAGE_TYPE_FAILURE)
            ->with('slugs')
            ->first();

        if ($page_setting instanceof PageSetting) {
            return $page_setting;
        }

        try {
            return $this->page_settings_bootstrap_service->bootstrapFailurePageSetting();
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[FailurePageService] failed to bootstrap failure page settings', [
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return null;
        }
    }

    /** @return array<string, mixed> */
    public function getViewData(PageSetting $page_setting, int $language_id, string $locale): array
    {
        try {
            $settings = $this->page_settings_bootstrap_service->normalizeFailureSettings(
                is_array($page_setting->settings) ? $page_setting->settings : [],
            );
            $localized = $this->resolveLocalized($settings, $language_id);
            $support = $this->resolveSupportContacts($settings, $language_id, $locale);
            $header_data = ($this->header_service)([
                'breadcrumbs' => [],
                'sluggable_type' => PageSetting::class,
                'slug' => $page_setting->getSlugByLanguageId($language_id),
            ]);

            return [
                'page_type' => string_value(config('page-settings.page_type.failure', 'failure')),
                'page_title' => string_value(Arr::get($localized, 'title', __('storefront/failure.fallbacks.title', [], $locale))),
                'title' => string_value(Arr::get($localized, 'title', '')),
                'description' => Arr::get($localized, 'description'),
                'images' => $this->resolveImages(Arr::get($settings, 'images', [])),
                'buttons' => [
                    'retry' => [
                        ...array_value(Arr::get($settings, 'buttons.retry', [])),
                        'label' => string_value(Arr::get($localized, 'retry_button.label', __('storefront/failure.buttons.retry', [], $locale))),
                    ],
                    'alternative_payment' => [
                        ...array_value(Arr::get($settings, 'buttons.alternative_payment', [])),
                        'label' => string_value(Arr::get($localized, 'alternative_payment_button.label', __('storefront/failure.buttons.alternative_payment', [], $locale))),
                    ],
                    'available_payment_methods' => array_value(Arr::get($settings, 'buttons.available_payment_methods', [])),
                ],
                'support' => $support,
                'payment_methods' => $this->resolvePaymentMethods($locale),
                'recovery' => [
                    'retry_url' => localized_route('localized.catalog.failure-order.retry', ['locale' => $locale]),
                    'payment_url' => localized_route('localized.catalog.failure-order.payment', ['locale' => $locale]),
                    'payment_method' => $this->failure_order_recovery_service->getRetryPaymentMethod(),
                    'retry_count' => integer_value(Arr::get($this->failure_order_recovery_service->getState(), 'retry_count', 0)),
                ],
                'header_data' => $header_data,
                'footer_data' => ($this->footer_service)(['categories' => $header_data['categories']]),
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[FailurePageService] failed to prepare storefront data', [
                'page_setting_id' => $page_setting->getKey(),
                'language_id' => $language_id,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function resolveLocalized(array $settings, int $language_id): array
    {
        $localized = array_value(Arr::get($settings, 'localized', []));
        $content = Arr::get($localized, (string) $language_id);

        if (is_array($content)) {
            return string_keyed_array($content);
        }

        $first = Arr::first($localized);

        return is_array($first) ? string_keyed_array($first) : [];
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function resolveSupportContacts(array $settings, int $language_id, string $locale): array
    {
        $support = array_value(Arr::get($settings, 'support_contacts', []));
        $contacts = $this->contacts_page_service->getViewData(
            $this->contacts_page_service->getStaticPageSetting() ?? new PageSetting(),
            $language_id,
        );

        return [
            'working_hours' => (bool) Arr::get($support, 'use_contacts_working_hours', true)
                ? (array) Arr::get($contacts, 'working_hours', [])
                : array_value(Arr::get($support, "working_hours.$language_id", [])),
            'phones' => (bool) Arr::get($support, 'use_contacts_phones', true)
                ? $this->normalizeSupportItems(Arr::get($contacts, 'phones', []), $locale)
                : $this->normalizeSupportItems(Arr::get($support, 'phones', []), $locale),
            'emails' => (bool) Arr::get($support, 'use_contacts_emails', true)
                ? $this->normalizeSupportItems(Arr::get($contacts, 'emails', []), $locale)
                : $this->normalizeSupportItems(Arr::get($support, 'emails', []), $locale),
        ];
    }

    /**
     * @param mixed $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSupportItems(mixed $items, string $locale): array
    {
        return collect(is_array($items) ? $items : [])
            ->map(fn (mixed $item): array => is_array($item)
                ? $item
                : ['value' => string_value($item)])
            ->filter(fn (array $item): bool => filled(Arr::get($item, 'value')))
            ->sortBy(fn (array $item): int => integer_value(Arr::get($item, 'sort_order', 0)))
            ->map(fn (array $item): array => [
                    'value' => Str::trim(string_value(Arr::get($item, 'value'))),
                    'type' => string_value(Arr::get($item, 'type', 'mobile')),
                    'custom_css_classes' => Str::squish(string_value(Arr::get($item, 'custom_css_classes', ''))),
                    'label' => string_value(Arr::get($item, 'value')),
                    'locale' => $locale,
                ])
            ->values()
            ->all();
    }

    /**
     * @param mixed $rows
     * @return array<int, array{urls: array<string, string>, width: int, height: int, custom_css_classes: string}>
     */
    private function resolveImages(mixed $rows): array
    {
        $resolved_images = collect(is_array($rows) ? $rows : [])
            ->filter(fn (mixed $row): bool => is_array($row) && filled(Arr::get($row, 'path')))
            ->sortBy(fn (array $row): int => integer_value(Arr::get($row, 'sort_order', 0)))
            ->map(function (array $row): ?array {
                $path = Str::ltrim(string_value(Arr::get($row, 'path')), '/');

                if (! Storage::disk('public')->exists($path)) {
                    return null;
                }

                $width = max(1, integer_value(Arr::get($row, 'width', 600)));
                $height = max(1, integer_value(Arr::get($row, 'height', 600)));

                return [
                    'urls' => multiple_convert_img_and_get_url(
                        $path,
                        $width,
                        $height,
                        (bool) Arr::get($row, 'is_square', true),
                        string_value(Arr::get($row, 'background', 'transparent')),
                    ),
                    'width' => $width,
                    'height' => $height,
                    'custom_css_classes' => Str::squish(string_value(Arr::get($row, 'custom_css_classes', ''))),
                ];
            })
            ->filter(fn (?array $image): bool => $image !== null)
            ->values()
            ->all();

        /** @var array<int, array{urls: array<string, string>, width: int, height: int, custom_css_classes: string}> $resolved_images */
        return $resolved_images;
    }

    /** @return array<int, array<string, mixed>> */
    private function resolvePaymentMethods(string $locale): array
    {
        $methods = [
            [
                'payment_method' => 'cash_on_delivery',
                'payment_name' => string_value(__('storefront/pages/checkout.payment_methods.cash_on_delivery', [], $locale)),
                'is_available' => true,
            ],
            $this->payment_upon_delivery_data_service->getCheckoutData(),
            $this->bank_transfer_data_service->getCheckoutData($locale),
        ];

        return collect($methods)
            ->filter(fn (array $method): bool => (bool) Arr::get($method, 'is_available', false))
            ->map(function (array $method) use ($locale): array {
                $payment_name = string_value(Arr::get($method, 'payment_name', ''));
                $translation_key = string_value(Arr::get($method, 'label_translation_key', ''));

                if ($payment_name === '' && $translation_key !== '') {
                    $payment_name = string_value(Lang::get($translation_key, [], $locale));
                }

                return [
                    'payment_method' => string_value(Arr::get($method, 'payment_method', '')),
                    'payment_name' => $payment_name,
                ];
            })
            ->filter(fn (array $method): bool => filled($method['payment_method']))
            ->values()
            ->all();
    }
}
