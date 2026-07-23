<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\Order\ThankYouOrderDataService;
use Illuminate\View\View;

class ThankYouController extends Controller
{
    public function index(
        HeaderService $header_service,
        FooterService $footer_service,
        ThankYouOrderDataService $thank_you_order_data_service,
        string $locale,
        string $order_number,
    ): View {
        $header_data = $header_service();
        $thank_you_data = $thank_you_order_data_service->getByOrderNumber($order_number, normalize_locale($locale));

        $mobile_device_type = config('devices.types.mobile');
        $tablet_device_type = config('devices.types.tablet');
        $current_device_type = config('devices.current_device_type');
        $background_image = in_array($current_device_type, [$mobile_device_type, $tablet_device_type], true)
            ? 'images/thank-you/mobile-bg-1.png'
            : 'images/thank-you/pc-bg-1.png';
        $background_image_data = [
            'path' => $background_image,
            'urls' => multiple_convert_img_and_get_url($background_image, 326, 326, true, 'transparent'),
            'width' => 326,
            'height' => 326,
        ];

        return view('catalog.pages.thank-you', [
            'header_data' => $header_data,
            'footer_data' => $footer_service([
                'categories' => $header_data['categories'],
            ]),
            'background_image_data' => $background_image_data,
            'page_type' => config('page-settings.page_type.thankyou', 'thankyou'),
            'thank_you_data' => $thank_you_data,
            'order_found' => $thank_you_data !== null,
            'requested_order_number' => $order_number,
            'breadcrumbs' => [
                breadcrumb(__('catalog/default.links.home'), localized_route('catalog.home')),
                breadcrumb(__('catalog/default.cart.labels.cart')),
            ],
        ]);
    }
}
