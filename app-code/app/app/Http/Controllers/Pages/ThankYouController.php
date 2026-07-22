<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\FooterService;
use App\Services\HeaderService;
use Illuminate\View\View;

class ThankYouController extends Controller
{
    /**
     * @param HeaderService $header_service
     * @param FooterService $footer_service
     *
     * @return View
     */
    public function index(HeaderService $header_service, FooterService $footer_service): View
    {
        $header_data = $header_service();

        $mobile_device_type    = config('devices.types.mobile');
        $tablet_device_type    = config('devices.types.tablet');
        $current_device_type   = config('devices.current_device_type');
        $background_image      = in_array($current_device_type, [$mobile_device_type, $tablet_device_type], true)
            ? 'images/thank-you/mobile-bg-1.png'
            : 'images/thank-you/pc-bg-1.png';
        $background_image_data = [
            'path'   => $background_image,
            'urls'   => multiple_convert_img_and_get_url($background_image, 326, 326, true, 'transparent'),
            'width'  => 326,
            'height' => 326,
        ];

        $data = [
            'header_data'           => $header_data,
            'footer_data'           => $footer_service([
                'categories' => $header_data['categories'],
            ]),
            'background_image_data' => $background_image_data,
            'page_type'             => config('page-settings.page_type.thankyou', 'thankyou'),
            'thank_you_data'        => [
                'order_number'   => 'SV-000124',
                'customer'       => [
                    'name'  => 'Олександр Козак',
                    'email' => 'example@example.com',
                    'phone' => '+38 (096) 690-64-12',
                ],
                'delivery'       => [
                    'address' => 'Київ, вул. Велика Васильківська, 12',
                    'method'  => 'Нова пошта',
                ],
                'products'       => [
                    [
                        'image_url'       => asset('storage/images/thank-you/mobile-bg-1.png'),
                        'name'            => 'Базова футболка SYVAK з довгою назвою товару',
                        'sku'             => 'TS-001-BLK',
                        'attributes'      => [
                            'Розмір' => 'M',
                            'Колір'  => 'Чорний',
                        ],
                        'quantity'        => 1,
                        'price_formatted' => '1 200 ₴',
                    ],
                    [
                        'image_url'       => asset('storage/images/thank-you/mobile-bg-1.png'),
                        'name'            => 'Шопер з принтом',
                        'sku'             => 'SH-004-NAT',
                        'attributes'      => [
                            'Матеріал' => 'Бавовна',
                        ],
                        'quantity'        => 2,
                        'price_formatted' => '900 ₴',
                    ],
                ],
                'summary'        => [
                    'payment_method'   => 'Оплата карткою',
                    'delivery_method'  => 'Нова пошта',
                    'delivery_address' => 'Київ, вул. Велика Васильківська, 12',
                    'subtotal'         => '2 100 ₴',
                    'packaging'        => '0 ₴',
                    'delivery_cost'    => 'За тарифами перевізника',
                    'total'            => '2 100 ₴',
                    'notes'            => 'Дякуємо за замовлення! Деталі підтвердження надіслані на вашу електронну пошту.',
                ],
                'products_count' => 2,
            ],
            'breadcrumbs'           => [
                breadcrumb(__('catalog/default.links.home'), localized_route('catalog.home')),
                breadcrumb(__('catalog/default.cart.labels.cart')),
            ],
        ];

        return view('catalog.pages.thank-you', $data);
    }
}
