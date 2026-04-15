@extends('catalog.layouts.main')

@section('content')
    <x-catalog::common.breadcrumbs :breadcrumbs="$breadcrumbs" />

    <section class="product" id="product">
        <div class="container">
            <div data-component="product" data-stock-state="{{ $product_view_data['is_in_stock'] === true ? 'in-stock' : 'out-of-stock' }}">
                <div data-block="top">
                    <x-catalog::common.img
                        class="object-cover"
                        :urls_data="$product_view_data['main_image']['urls']"
                        :size="$product_view_data['main_image']['width']"
                        :max-density="3"
                        sizes="100vw"
                        width="{{ $product_view_data['main_image']['width'] }}"
                        height="{{ $product_view_data['main_image']['height'] }}"
                        alt="{{ strip_tags($product_view_data['title']) }}"
                    />

                    <div data-block="gallery-thumbnails">
                        @foreach($product_view_data['gallery_images_data'] as $gallery_image_data)
                            <x-catalog::common.img
                                class="object-cover"
                                :urls_data="$gallery_image_data['urls']"
                                :size="$gallery_image_data['width']"
                                :max-density="3"
                                sizes="100vw"
                                width="{{ $gallery_image_data['width'] }}"
                                height="{{ $gallery_image_data['height'] }}"
                                alt="{{ strip_tags($product_view_data['title']) }}"
                            />
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
