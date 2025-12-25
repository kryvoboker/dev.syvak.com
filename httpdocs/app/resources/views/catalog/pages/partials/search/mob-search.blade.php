<div class="overlay overlay-open:translate-x-0 drawer drawer-start hidden mob-search bg-black"
     id="mob-search"
     role="dialog"
     tabindex="-1">
    <div class="drawer-header flex-col items-stretch pt-38px px-4 pb-6">
        <div class="flex items-center justify-between gap-x-2 mb-4">
            <button class="size-8 btn btn-text btn-circle"
                    type="button"
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    aria-controls="{{ __('catalog/default.aria_labels.back_to_main_mob_menu') }}"
                    data-overlay="#main-mob-menu">
                <span class="icon-[si--arrow-left-line] custom-icon size-8"></span>
            </button>

            <button class="size-8 btn btn-text btn-circle"
                    type="button"
                    aria-expanded="true"
                    aria-controls="{{ __('catalog/default.aria_labels.close_mob_search') }}"
                    data-overlay="#mob-search">
                <span class="icon-[iconamoon--close] custom-icon size-8"></span>
            </button>
        </div>

        <div class="flex flex-col gap-y-1 border-y border-y-opacity-light-gray-40% py-2">
            <form class="_needs-validation"
                  novalidate
                  action="#"
                  method="POST">
                @method('post')
                <div class="flex space-x-4">
                    <button type="submit" class="icon-[si--search-line] custom-icon my-auto size-6 shrink-0"></button>

                    <input class="w-full border-none"
                           id="mob-search"
                           type="search"
                           placeholder="{{ __('catalog/default.placeholders.search') }}"
                           required
                           minlength="3"
                           aria-label="{{ __('catalog/default.placeholders.search') }}"/>
                </div>

                <span class="_error">
                    {{ __('catalog/default.errors.keyword_min') }}
                </span>
            </form>
        </div>
    </div>

    <div class="mob-search-results drawer-body px-4">

    </div>

    <div class="drawer-footer gap-x-6 px-4">
        @foreach($header_data['socials'] as $social_data)
            <a class="custom-icon size-10" href="{{ $social_data['url'] }}">
                {!! $social_data['svg_icon'] !!}
            </a>
        @endforeach
    </div>
</div>
