@forelse($products_data as $product_data)
    <a class="flex items-center gap-x-2 border-b border-b-opacity-light-gray-40% pb-2"
       href="{{ $product_data['link'] }}">
        <x-catalog::common.img
            class="object-contain w-2/5"
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

        <div class="flex flex-col gap-y-2 w-3/5">
            <div class="font-cormorant-garamond text-lg uppercase line-clamp-2">
                {{ $product_data['descriptions']['name'] }}
            </div>

            <div class="text-11px font-light text-light-gray tracking-0.04em">
                {{ __('catalog/default.texts.sku', ['sku' => $product_data['sku']]) }}
            </div>

            <div class="flex flex-col gap-y-1 uppercase">
                @isset($product_data['discount'])
                    <div class="text-[.8em] text-light-red">
                        <del>{{ $product_data['price'] }}</del>
                    </div>

                    <div>
                        {{ $product_data['discount'] }}
                    </div>
                @else
                    <div>
                        {{ $product_data['price'] }}
                    </div>
                @endisset
            </div>
        </div>
    </a>
@empty
    <div class="font-bold text-center text-xl mb-2">
        {{ __('catalog/default.texts.products_not_found') }}
    </div>

    <x-catalog::common.img
        class="object-contain"
        :urls_data="$search_not_found_data['urls']"
        :size="$search_not_found_data['width']"
        sizes="(max-width: {{ $max_viewport_width }}px) {{ $search_not_found_data['width'] * 4 }}px,
                   (max-width: 1536px) {{ $search_not_found_data['width'] * 3 }}px,
                   (max-width: 1024px) {{ $search_not_found_data['width'] * 2 }}px,
                   (max-width: 640px) {{ $search_not_found_data['width'] }}px,
                   {{ $search_not_found_data['width'] * 2 }}px"
        width="{{ $search_not_found_data['width'] }}"
        height="{{ $search_not_found_data['height'] }}"
        alt="{{ __('catalog/default.texts.products_not_found') }}"
    />
@endforelse
