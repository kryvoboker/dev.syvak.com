<div class="overlay overlay-open:translate-x-0 drawer drawer-start hidden main-pc-menu bg-black lg:max-w-none"
     id="main-pc-menu"
     role="dialog"
     aria-modal="true"
     tabindex="-1">
    <div class="drawer-header flex items-center justify-between gap-x-6 border-b border-b-opacity-light-gray-40%
                px-8 py-4">
        <a href="{{ localized_route('catalog.home') }}" class="shrink-0" rel="nofollow">
            <x-catalog::common.img
                    class="object-contain"
                    :urls_data="$header_data['logo_data']['urls']"
                    :size="$header_data['logo_data']['width']"
                    width="{{ $header_data['logo_data']['width'] }}"
                    height="{{ $header_data['logo_data']['height'] }}"
                    alt="SYVAK"
            />
        </a>

        <span class="font-inter text-lg uppercase text-white">
            {{ __('catalog/default.buttons.catalog') }}
        </span>

        <button type="button"
                class="btn btn-text btn-circle size-8"
                aria-label="{{ __('catalog/default.aria_labels.close_pc_main_menu') }}"
                data-overlay="#main-pc-menu">
            <span class="icon-[iconamoon--close] custom-icon size-8"></span>
        </button>
    </div>

    <div class="drawer-body min-h-0 grow px-8 py-8">
        <div class="flex min-h-0 h-full flex-col gap-6 lg:flex-row">
            <nav class="min-h-0 min-w-0 grow overflow-y-auto lg:w-1/2 lg:grow-0"
                 aria-label="{{ __('catalog/default.buttons.catalog') }}">
                @foreach($header_data['categories'] ?? [] as $category_data)
                    <a class="group flex min-h-20 w-full items-center justify-between gap-x-6 border-b
                              border-b-opacity-light-gray-40% py-6 text-left font-cormorant-garamond
                              text-2xl font-bold leading-none uppercase text-white transition-colors
                              hover:bg-white/10 focus:bg-white/10 focus:outline-none lg:text-3xl"
                       href="{{ localized_route('localized.catalog.category.show', ['slug' => $category_data['slug']]) }}"
                       rel="nofollow"
                       data-pc-menu-item
                       data-preview-target="pc-menu-preview-{{ $category_data['id'] }}"
                       aria-controls="pc-menu-preview-{{ $category_data['id'] }}">
                        <span class="min-w-0 wrap-break-word">
                            {{ $category_data['descriptions']['name'] }}
                        </span>

                        <span class="icon-[ep--arrow-right] custom-icon size-6 shrink-0
                                     transition-transform group-hover:translate-x-1
                                     group-focus:translate-x-1"></span>
                    </a>
                @endforeach
            </nav>

            <div class="hidden min-h-0 min-w-0 grow lg:block lg:w-1/2"
                 aria-live="polite"
                 aria-label="{{ __('catalog/default.buttons.catalog') }}">
                @foreach($header_data['categories'] ?? [] as $category_data)
                    @if(isset($category_data['preview_image']))
                        <div class="hidden h-full w-full items-center justify-center overflow-hidden
                                    bg-white/5"
                             id="pc-menu-preview-{{ $category_data['id'] }}"
                             data-pc-menu-preview>
                            <x-catalog::common.img
                                    class="size-full object-contain"
                                    :urls_data="$category_data['preview_image']['urls']"
                                    :size="$category_data['preview_image']['width']"
                                    sizes="(min-width: 1440px) 50vw, 45vw"
                                    width="{{ $category_data['preview_image']['width'] }}"
                                    height="{{ $category_data['preview_image']['height'] }}"
                                    alt="{{ $category_data['preview_image']['alt'] }}"
                            />
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
