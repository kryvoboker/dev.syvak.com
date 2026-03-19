@php
    use Illuminate\Support\Arr;

    /**
     * Temporary placeholder contract for category products list until
     * backend data services are connected.
     */
    $sort_options = [
        'default'       => 'По умолчанию',
        'newest'        => 'Сначала новые',
        'bestsellers'   => 'Бестселлеры',
        'price_asc'     => 'Сначала дешевые',
        'price_desc'    => 'Сначала дорогие',
    ];

    $current_sort = 'default';

    /**
     * Reuses products card anatomy from ProductsCarousel module:
     * image -> title -> price/meta -> cart action.
     */
    $products = [
        [
            'name' => 'Футболка Гірська Кров',
            'price' => '1 500 UAH',
            'old_price' => null,
            'stock_text' => 'Немає в наявності',
            'url' => '#',
            'seed' => 'syvak-category-01',
            'layout' => 'product',
        ],
        [
            'name' => 'Футболка Обрана',
            'price' => '1 500 UAH',
            'old_price' => null,
            'stock_text' => 'Немає в наявності',
            'url' => '#',
            'seed' => 'syvak-category-02',
            'layout' => 'product',
        ],
        [
            'name' => 'Title Title Title Titletitletitle Title',
            'price' => '1 500 UAH',
            'old_price' => null,
            'stock_text' => 'Немає в наявності',
            'url' => '#',
            'seed' => 'syvak-category-03',
            'layout' => 'product',
        ],
        [
            'name' => 'Футболка Маковій',
            'price' => '1 500 UAH',
            'old_price' => '1 500 UAH',
            'stock_text' => 'Немає в наявності',
            'url' => '#',
            'seed' => 'syvak-category-04',
            'layout' => 'product',
        ],
        [
            'name' => 'Title Title Title Titletitletitle Title',
            'price' => '1 500 UAH',
            'old_price' => null,
            'stock_text' => 'Немає в наявності',
            'url' => '#',
            'seed' => 'syvak-category-05',
            'layout' => 'mixed',
        ],
        [
            'name' => 'Футболка Маковій',
            'price' => '1 500 UAH',
            'old_price' => null,
            'stock_text' => 'Немає в наявності',
            'url' => '#',
            'seed' => 'syvak-category-06',
            'layout' => 'product',
        ],
        [
            'name' => 'Футболка Обрана',
            'price' => '1 500 UAH',
            'old_price' => null,
            'stock_text' => 'Немає в наявності',
            'url' => '#',
            'seed' => 'syvak-category-07',
            'layout' => 'product',
        ],
        [
            'name' => 'Title Title Title Titletitletitle Title',
            'price' => '1 500 UAH',
            'old_price' => null,
            'stock_text' => 'Немає в наявності',
            'url' => '#',
            'seed' => 'syvak-category-08',
            'layout' => 'mixed',
        ],
        [
            'name' => 'Футболка Гірська Кров',
            'price' => '1 500 UAH',
            'old_price' => null,
            'stock_text' => 'Немає в наявності',
            'url' => '#',
            'seed' => 'syvak-category-09',
            'layout' => 'product',
        ],
        [
            'name' => 'Футболка Маковій',
            'price' => '1 500 UAH',
            'old_price' => null,
            'stock_text' => 'Немає в наявності',
            'url' => '#',
            'seed' => 'syvak-category-10',
            'layout' => 'product',
        ],
    ];

    /**
     * Generates placeholder responsive image URLs for x-catalog::common.img.
     */
    $build_placeholder_urls = static function (string $seed, int $width, int $height): array {
        $base = 'https://picsum.photos/seed/' . rawurlencode($seed);

        return [
            'original_thumb' => $base . '/' . max($width * 4, $width) . '/' . max($height * 4, $height),
            'thumb_1x' => $base . '/' . $width . '/' . $height,
            'thumb_2x' => $base . '/' . ($width * 2) . '/' . ($height * 2),
            'thumb_3x' => $base . '/' . ($width * 3) . '/' . ($height * 3),
            'thumb_4x' => $base . '/' . ($width * 4) . '/' . ($height * 4),
        ];
    };
@endphp

<section class="category-products-list-section border-b border-opacity-light-gray-40% py-10 md:py-12 lg:py-14 2xl:py-16"
         aria-label="Category products list">
    <div class="container">
        <div class="flex items-end justify-between gap-4 md:gap-6 mb-6 md:mb-7 lg:mb-8">
            <h2 class="section-title">
                КОЛЕКЦІЇ
            </h2>

            <div class="flex items-center gap-x-1 md:gap-x-2 lg:gap-x-3">
                <button class="open-category-filter-drawer-btn default-btn border border-opacity-light-gray-40% px-3 py-1.5 text-11px
                               md:text-sm lg:text-base uppercase tracking-0.04em hover:border-white duration-200 ease-in-out"
                        type="button"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        aria-controls="category-filter-drawer">
                    ФІЛЬТР
                </button>

                <div class="dropdown category-sort-dropdown relative">
                    <button class="dropdown-toggle default-btn flex items-center gap-x-2 border border-opacity-light-gray-40% px-3 py-1.5 text-11px
                                   md:text-sm lg:text-base uppercase tracking-0.04em hover:border-white duration-200 ease-in-out"
                            type="button"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            aria-label="Sort products dropdown">
                        <span>СОРТУВАННЯ</span>
                        <span class="icon-[solar--alt-arrow-down-line-duotone] dropdown-open:rotate-180 size-19px text-white duration-100 ease-in-out"></span>
                    </button>

                    <ul class="dropdown-menu dropdown-open:opacity-100 hidden min-w-52 border border-opacity-light-gray-40% bg-black p-3 md:p-4"
                        role="menu"
                        aria-orientation="vertical">
                        <button class="close-category-sort-dropdown-btn block ms-auto mb-1"
                                type="button"
                                aria-label="Close sort dropdown">
                            <span class="icon-[ic--baseline-close] size-5 text-white"></span>
                        </button>

                        @foreach($sort_options as $sort_key => $sort_label)
                            @php
                                $is_current_sort = $sort_key === $current_sort;
                            @endphp

                            <li class="border-b border-opacity-light-gray-40% last:border-b-0">
                                <button class="w-full text-left p-2 text-11px md:text-sm lg:text-base uppercase tracking-0.04em hover:text-white duration-200 ease-in-out
                                              {{ $is_current_sort ? 'text-white' : 'text-light-gray' }}"
                                        type="button"
                                        aria-current="{{ $is_current_sort ? 'true' : 'false' }}">
                                    {{ $sort_label }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 bp1920px:grid-cols-4 gap-x-2 gap-y-3 md:gap-x-3 md:gap-y-4 lg:gap-x-4 lg:gap-y-5">
            @foreach($products as $product_index => $product)
                @php
                    $name = (string) Arr::get($product, 'name', 'Product name');
                    $price = (string) Arr::get($product, 'price', '0 UAH');
                    $old_price = Arr::get($product, 'old_price');
                    $stock_text = (string) Arr::get($product, 'stock_text', '');
                    $url = (string) Arr::get($product, 'url', '#');
                    $layout = (string) Arr::get($product, 'layout', 'product');
                    $seed = (string) Arr::get($product, 'seed', 'syvak-category');
                    $is_mixed_layout = $layout === 'mixed';
                    $image_width = $is_mixed_layout ? 860 : 420;
                    $image_height = $is_mixed_layout ? 420 : 420;
                    $image_urls_data = $build_placeholder_urls($seed, $image_width, $image_height);
                @endphp

                <article class="products-carousel-card flex h-full flex-col gap-2 md:gap-3 lg:gap-4 {{ $is_mixed_layout ? 'col-span-2 md:col-span-1 bp1920px:col-span-2' : '' }}">
                    <div class="font-light text-light-gray text-11px md:text-sm">
                        @if(($product_index + 1) < 10)
                            / 0{{ $product_index + 1 }}
                        @else
                            / {{ $product_index + 1 }}
                        @endif
                    </div>

                    <a class="products-carousel-img-container flex items-center justify-center overflow-hidden"
                       href="{{ $url }}"
                       aria-label="{{ $name }}">
                        <x-catalog::common.img
                            class="object-cover w-full transition-transform hover:scale-105 duration-500 ease-in-out"
                            :urls_data="$image_urls_data"
                            :size="$image_width"
                            :max-density="3"
                            sizes="100vw"
                            width="{{ $image_width }}"
                            height="{{ $image_height }}"
                            alt="{{ $name }}"
                        />
                    </a>

                    <div class="products-carousel-info flex min-h-20 flex-col gap-2">
                        <a class="products-carousel-card-title prod-list__name"
                           href="{{ $url }}"
                           aria-label="{{ $name }}">
                            {{ $name }}
                        </a>

                        <div class="flex items-center justify-between gap-x-3">
                            <div class="products-carousel-card-price text-11px md:text-sm lg:text-base 2xl:text-lg uppercase tracking-0.04em text-light-gray">
                                {{ $price }}
                                @if(filled($old_price))
                                    <span class="line-through text-light-gray/80">
                                        {{ $old_price }}
                                    </span>
                                @endif
                            </div>

                            <button class="add-to-cart"
                                    type="button"
                                    aria-label="Add product to cart">
                                <span class="icon-[solar--cart-5-linear] custom-icon"></span>
                            </button>
                        </div>

                        @if(filled($stock_text))
                            <div class="text-11px md:text-sm uppercase tracking-0.04em text-light-gray">
                                {{ $stock_text }}
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-5 md:mt-6 lg:mt-7 flex justify-center">
            <button class="default-btn uppercase tracking-0.04em text-light-gray hover:text-white duration-200 ease-in-out"
                    type="button">
                ПОКАЗАТИ ЩЕ
                <span class="icon-[solar--alt-arrow-down-line-duotone] size-4 md:size-5"></span>
            </button>
        </div>
    </div>
</section>

<div class="overlay overlay-open:translate-x-0 drawer drawer-start hidden category-filter-drawer bg-black"
     id="category-filter-drawer"
     role="dialog"
     tabindex="-1">
    <div class="drawer-header flex-col items-stretch pt-38px px-4 pb-6">
        <button type="button"
                class="size-8 btn btn-text btn-circle ms-auto mb-4"
                aria-expanded="true"
                aria-controls="Close category filter drawer"
                data-overlay="#category-filter-drawer">
            <span class="icon-[iconamoon--close] custom-icon size-8"></span>
        </button>

        <h4>Фільтр</h4>
    </div>

    <div class="drawer-body px-4">
        <p class="text-light-gray text-sm md:text-base uppercase tracking-0.04em">
            Фільтр товарів буде тут
        </p>
    </div>
</div>
