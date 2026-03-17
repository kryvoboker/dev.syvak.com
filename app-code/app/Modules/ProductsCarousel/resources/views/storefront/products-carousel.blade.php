@php
    use Illuminate\Support\Str;

    $carousel_dom_id = 'products-carousel-' . ($products_carousel_module_data['instance_id'] ?? Str::random(6));
    $section_title = (string) ($products_carousel_module_data['module_name_for_user'] ?? '');
    $section_description = (string) ($products_carousel_module_data['short_description_for_user'] ?? '');
    $current_page_type = (string) ($page_type ?? '');
    $page_types = collect($products_carousel_module_data['page_types'] ?? [])
        ->filter(fn (mixed $module_page_type): bool => is_string($module_page_type) && filled($module_page_type))
        ->values()
        ->all();

    $placeholder_products = [
        ['name' => 'ФУТБОЛКА ГІРСЬКА КРОВ', 'price' => '1 500 UAH'],
        ['name' => 'ФУТБОЛКА ОБРАНА', 'price' => '1 500 UAH'],
        ['name' => 'ФУТБОЛКА МАКОВІЙ', 'price' => '1 500 UAH'],
        ['name' => 'ФУТБОЛКА ДИКЕ ПОЛЕ', 'price' => '1 500 UAH'],
        ['name' => 'ФУТБОЛКА СВІТЛОТІНЬ', 'price' => '1 500 UAH'],
    ];

    $products = collect($products_carousel_module_data['products'] ?? [])
        ->filter(fn (mixed $product): bool => is_array($product))
        ->values();

    if ($products->isEmpty()) {
        $products = collect($placeholder_products)
            ->map(function (array $product): array {
                return [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'url' => '#',
                    'image_data' => [
                        'urls' => multiple_convert_img_and_get_url(
                            config('app.images.product.no_image'),
                            420,
                            420,
                            is_square: false,
                        ),
                        'width' => 420,
                        'height' => 420,
                    ],
                ];
            })
            ->values();
    }
@endphp

<section class="products-carousel-section" aria-label="Products carousel section">
    <div class="container products-carousel-head">
        @if(filled($section_title))
            <h2 class="products-carousel-title">{{ $section_title }}</h2>
        @endif
        @if(filled($section_description))
            <p class="products-carousel-description">{{ $section_description }}</p>
        @endif
    </div>

    <div id="{{ $carousel_dom_id }}"
         class="container products-carousel-root --prevent-on-load-init"
         data-products-carousel
         data-page-types='@json($page_types)'
         data-current-page-type="{{ $current_page_type }}"
         data-carousel='{"loadingClasses":"opacity-0,opacity-100 transition-opacity duration-500","isAutoHeight":true,"isInfiniteLoop":true,"slidesQty":{"xs":1,"sm":1,"md":2,"lg":3,"2xl":5}, "dotsItemClasses": "carousel-dot size-1.5 bg-light-gray carousel-active:size-2.5 carousel-active:ease-in-out carousel-active:duration-200"}'>
        <div class="carousel products-carousel-track">
            <div class="carousel-body products-carousel-body">
                @foreach($products as $product)
                    @php
                        $name = (string) ($product['name'] ?? 'Product name');
                        $price = (string) ($product['price'] ?? '0 UAH');
                        $url = (string) ($product['url'] ?? '#');
                        $image_data = $product['image_data']['urls'] ?? [];
                        $image_src = (string) ($image_data['thumb_1x'] ?? $image_data['original_thumb'] ?? $image_data['original'] ?? '');
                        $image_width = (int) ($product['image_data']['width'] ?? 420);
                        $image_height = (int) ($product['image_data']['height'] ?? 420);
                    @endphp

                    <article class="carousel-slide products-carousel-slide">
                        <a href="{{ $url }}" class="products-carousel-card" aria-label="{{ $name }}">
                            <div class="products-carousel-card-image-wrap">
                                <img src="{{ $image_src }}"
                                     alt="{{ $name }}"
                                     class="products-carousel-card-image"
                                     width="{{ $image_width }}"
                                     height="{{ $image_height }}"
                                     loading="lazy"
                                     decoding="async">
                            </div>

                            <div class="products-carousel-card-content">
                                <h3 class="products-carousel-card-title">{{ $name }}</h3>
                                <p class="products-carousel-card-price">{{ $price }}</p>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        </div>

        @if($products->count() > 1)
            <div class="products-carousel-controls">
                <button class="carousel-prev products-carousel-nav" type="button" aria-label="Previous product">
                    <span class="icon-[material-symbols-light--arrow-back-rounded] products-carousel-nav-icon"></span>
                </button>
                <button class="carousel-next products-carousel-nav" type="button" aria-label="Next product">
                    <span class="icon-[material-symbols-light--arrow-forward-rounded] products-carousel-nav-icon"></span>
                </button>
            </div>

            <div class="carousel-pagination products-carousel-pagination"></div>
        @endif
    </div>
</section>
