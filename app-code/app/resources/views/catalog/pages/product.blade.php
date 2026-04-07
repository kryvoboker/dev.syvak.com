@extends('catalog.layouts.main')

@section('content')
    <x-catalog::common.breadcrumbs :breadcrumbs="$breadcrumbs" />

    <section class="product" id="product">
        <div class="container">
            <div data-component="product" data-stock-state="{{ data_get($product_view_data, 'is_in_stock', false) ? 'in-stock' : 'out-of-stock' }}">
                <div data-block="top">
                    <div data-block="gallery-main">
                        <button type="button" data-role="gallery-prev"></button>

                        @if(filled((string) data_get($product_view_data, 'main_image_path', '')))
                            <img
                                src="{{ convert_img_and_get_url((string) data_get($product_view_data, 'main_image_path', ''), 900, 1100, false) }}"
                                alt="{{ (string) data_get($product_view_data, 'title', '') }}"
                            >
                        @else
                            <div data-role="gallery-image-placeholder"></div>
                        @endif

                        <button type="button" data-role="gallery-next"></button>
                    </div>

                    <div data-block="product-content">
                        <div data-block="gallery-thumbnails">
                            <ul>
                                @foreach((array) data_get($product_view_data, 'gallery_images', []) as $gallery_image)
                                    <li>
                                        <img
                                            src="{{ convert_img_and_get_url((string) (is_array($gallery_image) ? data_get($gallery_image, 'image', data_get($gallery_image, '0', '')) : $gallery_image), 220, 280, false) }}"
                                            alt="{{ (string) data_get($product_view_data, 'title', '') }}"
                                        >
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div data-block="product-summary">
                            <h1>{{ (string) data_get($product_view_data, 'title', '') }}</h1>
                            <p>{{ __('catalog/default.texts.sku', ['sku' => (string) data_get($product_view_data, 'sku', '')]) }}</p>
                            <p>{{ (string) data_get($product_view_data, 'price_formatted', '') }}</p>
                        </div>

                        <form data-block="product-options" action="#" method="post">
                            @foreach((array) data_get($product_view_data, 'option_groups', []) as $option_group)
                                <fieldset data-option-key="{{ (string) data_get($option_group, 'key', '') }}">
                                    <legend>{{ (string) data_get($option_group, 'label', '') }}</legend>

                                    @if(collect(data_get($option_group, 'values', []))->isNotEmpty())
                                        <ul>
                                            @foreach((array) data_get($option_group, 'values', []) as $option_value)
                                                <li>
                                                    <button type="button">{{ (string) $option_value }}</button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </fieldset>
                            @endforeach

                            <a href="#">{{ (string) data_get($product_view_data, 'labels.size_help', '') }}</a>
                        </form>

                        @if(data_get($product_view_data, 'is_in_stock', false))
                            <div data-block="purchase-actions">
                                <button type="button">{{ (string) data_get($product_view_data, 'labels.buy_one_click', '') }}</button>
                                <button type="button">{{ (string) data_get($product_view_data, 'labels.add_to_cart', '') }}</button>
                            </div>
                        @else
                            <div data-block="notify-actions">
                                <p>{{ (string) data_get($product_view_data, 'labels.notify', '') }}</p>
                                <button type="button">{{ (string) data_get($product_view_data, 'labels.telegram', '') }}</button>
                            </div>
                        @endif
                    </div>
                </div>

                <div data-block="bottom">
                    @foreach((array) data_get($product_view_data, 'details_sections', []) as $details_section)
                        <section data-details-key="{{ (string) data_get($details_section, 'key', '') }}">
                            <h2>{{ (string) data_get($details_section, 'label', '') }}</h2>

                            @if(collect(data_get($details_section, 'items', []))->isNotEmpty())
                                <ul>
                                    @foreach((array) data_get($details_section, 'items', []) as $details_item)
                                        <li>{{ (string) $details_item }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <ul>
                                    <li></li>
                                </ul>
                            @endif
                        </section>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
