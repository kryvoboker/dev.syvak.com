@forelse($products_data as $product_data)
    <a class="flex items-center gap-x-2 lg:gap-x-6 border-b border-b-opacity-light-gray-40% pb-2"
       href="{{ $product_data['link'] }}">
        <x-catalog::common.img
            class="object-contain w-1/3"
            :urls_data="$product_data['image_data']['urls']"
            :size="$product_data['image_data']['width']"
            sizes="(max-width: {{ $max_viewport_width }}px) {{ $product_data['image_data']['width'] * 4 }}px,
                   (max-width: 1536px) {{ $product_data['image_data']['width'] * 3 }}px,
                   (max-width: 1024px) {{ $product_data['image_data']['width'] * 2 }}px,
                   (max-width: 640px) {{ $product_data['image_data']['width'] }}px,
                   {{ $product_data['image_data']['width'] * 2 }}px"
            width="{{ $product_data['image_data']['width'] }}"
            height="{{ $product_data['image_data']['height'] }}"
            :alt="$product_data['descriptions']['name']"
        />

        <div class="flex flex-col gap-y-2 w-2/3">
            <div class="font-cormorant-garamond text-lg md:text-2xl bp1920px:text-4xl uppercase line-clamp-2">
                {{ $product_data['descriptions']['name'] }}
            </div>

            <div class="text-11px lg:text-base bp1920px:text-lg font-light text-light-gray tracking-0.04em">
                {{ __('catalog/default.texts.sku', ['sku' => $product_data['sku']]) }}
            </div>

            <div class="flex flex-col gap-y-1 uppercase">
                @if(!empty($product_data['discount']) && isset($product_data['discount']['discounted_price']))
                    <div class="text-[.8em] text-light-red">
                        <del>{{ $product_data['price'] }}</del>
                    </div>

                    <div>
                        {{ $product_data['discount']['discounted_price'] }}
                    </div>
                @else
                    <div>
                        {{ $product_data['price'] }}
                    </div>
                @endif
            </div>
        </div>
    </a>
@empty
    <div class="flex flex-col items-center gap-y-2 md:col-span-2">
        <div class="font-bold text-center text-xl">
            {{ __('catalog/default.texts.products_not_found') }}
        </div>

        <x-catalog::common.img
            class="object-contain"
            :urls_data="$search_not_found_img_data['urls']"
            :size="$search_not_found_img_data['width']"
            sizes="(max-width: {{ $max_viewport_width }}px) {{ $search_not_found_img_data['width'] * 4 }}px,
                       (max-width: 1536px) {{ $search_not_found_img_data['width'] * 3 }}px,
                       (max-width: 1024px) {{ $search_not_found_img_data['width'] * 2 }}px,
                       (max-width: 640px) {{ $search_not_found_img_data['width'] }}px,
                       {{ $search_not_found_img_data['width'] * 2 }}px"
            width="{{ $search_not_found_img_data['width'] }}"
            height="{{ $search_not_found_img_data['height'] }}"
            alt="{{ __('catalog/default.texts.products_not_found') }}"
        />
    </div>
@endforelse
