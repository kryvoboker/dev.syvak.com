<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pages\ContactsFormRequest;
use App\Models\ApplicationSettings\Language;
use App\Models\PageSettings\PageSetting;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\PageSettings\ContactsFormDeliveryService;
use App\Services\PageSettings\ContactsPageService;
use App\Services\PageSettings\FailurePageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ContactsController extends Controller
{
    /**
     * @param HeaderService       $header_service
     * @param FooterService       $footer_service
     * @param ContactsPageService $contacts_page_service
     * @param string              $locale
     * @param string              $slug
     *
     * @throws Throwable
     * @return View
     */
    public function show(
        HeaderService $header_service,
        FooterService $footer_service,
        ContactsPageService $contacts_page_service,
        string $locale,
        string $slug,
        ?FailurePageService $failure_page_service = null,
    ): View {
        $locale = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);

        if (!$language instanceof Language) {
            throw new NotFoundHttpException();
        }

        $failure_page_setting = ($failure_page_service ?? app(FailurePageService::class))
            ->findBySlug($slug, (int) $language->id);

        if ($failure_page_setting instanceof PageSetting) {
            return view('storefront.pages.failure-order', [
                ...($failure_page_service ?? app(FailurePageService::class))
                    ->getViewData($failure_page_setting, (int) $language->id, $locale),
                'breadcrumbs' => [
                    breadcrumb(__('storefront/default.links.home'), localized_route('catalog.home')),
                ],
            ]);
        }

        $page_setting = $contacts_page_service->findBySlug($slug, (int)$language->id);

        if (!$page_setting instanceof PageSetting) {
            throw new NotFoundHttpException();
        }

        return $this->renderPage(
            header_service       : $header_service,
            footer_service       : $footer_service,
            contacts_page_service: $contacts_page_service,
            page_setting         : $page_setting,
            language_id          : (int)$language->id,
            slug                 : $slug,
            form_route_name      : 'localized.catalog.contacts.submit',
            form_route_params    : ['slug' => $slug],
        );
    }

    /**
     * @param HeaderService       $header_service
     * @param FooterService       $footer_service
     * @param ContactsPageService $contacts_page_service
     * @param string              $locale
     *
     * @throws Throwable
     * @return View|RedirectResponse
     */
    public function showStatic(
        HeaderService $header_service,
        FooterService $footer_service,
        ContactsPageService $contacts_page_service,
        string $locale,
    ): View|RedirectResponse {
        $data = $this->tryResolveDataForStatic($contacts_page_service, $locale);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        $page_setting = $data['page_setting'];
        $language = $data['language'];

        return $this->renderPage(
            header_service       : $header_service,
            footer_service       : $footer_service,
            contacts_page_service: $contacts_page_service,
            page_setting         : $page_setting,
            language_id          : (int)$language->id,
            slug                 : null,
            form_route_name      : 'localized.catalog.contacts.static.submit',
            form_route_params    : [],
        );
    }

    /**
     * @param ContactsFormRequest         $request
     * @param ContactsPageService         $contacts_page_service
     * @param ContactsFormDeliveryService $delivery_service
     * @param string                      $locale
     *
     * @return RedirectResponse
     */
    public function submitStatic(
        ContactsFormRequest $request,
        ContactsPageService $contacts_page_service,
        ContactsFormDeliveryService $delivery_service,
        string $locale,
    ): RedirectResponse {
        $data = $this->tryResolveDataForStatic($contacts_page_service, $locale);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        $page_setting = $data['page_setting'];
        $language = $data['language'];
        $locale = $data['locale'];

        return $this->deliverForm(
            request              : $request,
            delivery_service     : $delivery_service,
            page_setting         : $page_setting,
            language_id          : (int)$language->id,
            locale               : $locale,
            redirect_route_name  : 'localized.catalog.contacts.static.show',
            redirect_route_params: ['locale' => $locale],
        );
    }

    /**
     * @param ContactsPageService $contacts_page_service
     * @param string              $locale
     *
     * @return array{
     *     locale: string,
     *     language: Language,
     *     page_setting: PageSetting,
     * }|RedirectResponse
     */
    private function tryResolveDataForStatic(
        ContactsPageService $contacts_page_service,
        string $locale,
    ): array|RedirectResponse {
        $locale = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);
        $page_setting = $contacts_page_service->getStaticPageSetting();

        if (!$language instanceof Language || !$page_setting instanceof PageSetting) {
            throw new NotFoundHttpException();
        }

        $canonical_slug = $page_setting->getSlugByLanguageId((int)$language->id);

        if (filled($canonical_slug) && $canonical_slug !== 'contacts') {
            return redirect()->route('localized.catalog.contacts.show', [
                'locale' => $locale,
                'slug' => $canonical_slug,
            ]);
        }

        return compact('locale', 'language', 'page_setting');
    }

    /**
     * @param HeaderService       $header_service
     * @param FooterService       $footer_service
     * @param ContactsPageService $contacts_page_service
     * @param PageSetting         $page_setting
     * @param int                 $language_id
     * @param string|null         $slug
     * @param string              $form_route_name
     * @param array               $form_route_params
     *
     * @throws Throwable
     * @return View
     */
    private function renderPage(
        HeaderService $header_service,
        FooterService $footer_service,
        ContactsPageService $contacts_page_service,
        PageSetting $page_setting,
        int $language_id,
        ?string $slug,
        string $form_route_name,
        array $form_route_params,
    ): View {
        $header_data = $header_service([
            'sluggable_type' => PageSetting::class,
            'slug' => $slug,
        ]);
        $contacts_data = $contacts_page_service->getViewData($page_setting, $language_id);
        $data = [
            'header_data' => $header_data,
            'footer_data' => $footer_service([
                'categories' => $header_data['categories'],
            ]),
            'page_type' => config('page-settings.page_type.contacts', 'contacts'),
            'page_title' => (string)data_get($contacts_data, 'title'),
            'contacts_data' => $contacts_data,
            'form_action' => localized_route($form_route_name, $form_route_params),
            'breadcrumbs' => [
                breadcrumb(__('storefront/default.links.home'), localized_route('catalog.home')),
                breadcrumb((string)data_get($contacts_data, 'title', __('storefront/contacts.fallbacks.title'))),
            ],
        ];

        return view('storefront.pages.contacts', $data);
    }

    /**
     * @param ContactsFormRequest         $request
     * @param ContactsPageService         $contacts_page_service
     * @param ContactsFormDeliveryService $delivery_service
     * @param string                      $locale
     * @param string                      $slug
     *
     * @return RedirectResponse
     */
    public function submit(
        ContactsFormRequest $request,
        ContactsPageService $contacts_page_service,
        ContactsFormDeliveryService $delivery_service,
        string $locale,
        string $slug,
    ): RedirectResponse {
        $locale = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);

        if (!$language instanceof Language) {
            throw new NotFoundHttpException();
        }

        $page_setting = $contacts_page_service->findBySlug($slug, (int)$language->id);

        if (!$page_setting instanceof PageSetting) {
            throw new NotFoundHttpException();
        }

        return $this->deliverForm(
            request              : $request,
            delivery_service     : $delivery_service,
            page_setting         : $page_setting,
            language_id          : (int)$language->id,
            locale               : $locale,
            redirect_route_name  : 'localized.catalog.contacts.show',
            redirect_route_params: ['locale' => $locale, 'slug' => $slug],
        );
    }

    /**
     * @param ContactsFormRequest         $request
     * @param ContactsFormDeliveryService $delivery_service
     * @param PageSetting                 $page_setting
     * @param int                         $language_id
     * @param string                      $locale
     * @param string                      $redirect_route_name
     * @param array<string, mixed>        $redirect_route_params
     *
     * @return RedirectResponse
     */
    private function deliverForm(
        ContactsFormRequest $request,
        ContactsFormDeliveryService $delivery_service,
        PageSetting $page_setting,
        int $language_id,
        string $locale,
        string $redirect_route_name,
        array $redirect_route_params,
    ): RedirectResponse {
        try {
            $delivery_service->deliver(
                page_setting: $page_setting,
                locale      : $locale,
                language_id : $language_id,
                data        : $request->validated(),
                file        : $request->file('file'),
            );

            return redirect()
                ->route($redirect_route_name, $redirect_route_params)
                ->with('contacts_form_success', true);
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()
                ->route($redirect_route_name, $redirect_route_params)
                ->withErrors(['contact_form' => __('storefront/contacts.errors.delivery_failed')])
                ->withInput($request->except('file'));
        }
    }
}
