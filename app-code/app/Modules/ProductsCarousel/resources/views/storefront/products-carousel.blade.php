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
    <div class="container">
        <div class="flex gap-10 justify-between mb-8">
            @if(filled($section_title))
                <h2 class="section-title shrink-0">
                    {{ $section_title }}
                </h2>
            @endif
            @if(filled($section_description))
                <p class="products-carousel-description">
                    {{ $section_description }}
                </p>
            @endif
        </div>

        <div id="{{ $carousel_dom_id }}"
             class="container --prevent-on-load-init relative"
             data-products-carousel
             data-page-types='@json($page_types)'
             data-current-page-type="{{ $current_page_type }}"
             data-carousel='{"loadingClasses":"opacity-0, opacity-100 transition-opacity easy duration-500","isAutoHeight":true,"isInfiniteLoop":true, "isDraggable": true, "slidesQty":{"xs":1,"sm":1,"md":2,"lg":3,"2xl":5}, "dotsItemClasses": "carousel-dot size-1.5 bg-light-gray carousel-active:size-2.5 carousel-active:ease-in-out carousel-active:duration-200", "isAutoPlay": false}'>
            <div class="carousel products-carousel-track rounded-none">
                <div class="carousel-body products-carousel-body">
                    @foreach($products as $product)
                        @php
                            $name = (string) ($product['name'] ?? 'Product name');
                            $price = (string) ($product['price'] ?? '0 UAH');
                            $url = (string) ($product['url'] ?? '#');
                            $image_urls_data = $product['image_data']['urls'] ?? [];
                            $image_width = (int) ($product['image_data']['width'] ?? 420);
                            $image_height = (int) ($product['image_data']['height'] ?? 420);
                        @endphp

                        <div class="carousel-slide products-carousel-slide">
                            <a href="{{ $url }}" class="products-carousel-card" aria-label="{{ $name }}">
                                <div class="flex items-center justify-center">
                                    <x-catalog::common.img
                                        class="object-contain transition-transform hover:scale-105 duration-500 ease-in-out"
                                        :urls_data="$image_urls_data"
                                        :size="$image_width"
                                        :max-density="3"
                                        sizes="100vw"
                                        width="{{ $image_width }}"
                                        height="{{ $image_height }}"
                                        alt="{{ $name }}"
                                    />
                                </div>

                                <div class="flex min-h-20 flex-col gap-2">
                                    <h3 class="products-carousel-card-title">{{ $name }}</h3>
                                    <p class="products-carousel-card-price">{{ $price }}</p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($products->count() > 1)
                <button class="carousel-prev start-0 carousel-nav"
                        @style("top: calc(($image_height / 2) / var(--base-font-size) * 1rem * -1); transform: translateY(calc(($image_height * 0.1) / var(--base-font-size) * 1rem));")
                        type="button"
                        aria-label="Previous product">
                    <span class="icon-[mynaui--arrow-left] size-6"></span>
                </button>

                <button class="carousel-next end-0 carousel-nav"
                        @style("top: calc(($image_height / 2) / var(--base-font-size) * 1rem * -1); transform: translateY(calc(($image_height * 0.1) / var(--base-font-size) * 1rem));")
                        type="button"
                        aria-label="Next product">
                    <span class="icon-[mynaui--arrow-right] size-6"></span>
                </button>

                <div class="carousel-pagination"></div>
            @endif
        </div>
    </div>
</section>
