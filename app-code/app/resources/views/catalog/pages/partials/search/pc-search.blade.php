<div class="overlay overlay-open:translate-y-0 drawer drawer-top hidden pc-search-container max-h-none bg-black"
     id="pc-search-container"
     role="dialog"
     tabindex="-1">
    <div class="loader fixed top-0 left-0 hidden items-center justify-center size-full bg-black/70 z-10">
        <span class="loading loading-spinner size-10 text-white"></span>
    </div>

    <div class="drawer-header flex-col items-stretch pt-38px px-4 pb-6">
        <button class="size-8 btn btn-text btn-circle ms-auto mb-4"
                type="button"
                aria-expanded="true"
                aria-controls="{{ __('catalog/default.aria_labels.close_mob_search') }}"
                data-overlay="#pc-search-container">
            <span class="icon-[iconamoon--close] custom-icon size-8"></span>
        </button>

        <div class="flex flex-col gap-y-1 border-y border-y-opacity-light-gray-40% py-2 md:px-8 md:py-20px bp1920px:py-11">
            <form class="pc-search-form _needs-validation"
                  novalidate
                  data-ajax-search-url="{{ localizedRoute('localized.catalog.search.index') }}"
                  action="{{ localizedRoute('localized.catalog.search.show') }}"
                  method="GET">
                <div class="flex space-x-4">
                    <button class="pc-search-btn icon-[si--search-line] custom-icon my-auto size-6 shrink-0" type="submit"></button>

                    <input class="pc-search-input w-full border-none"
                           id="pc-search"
                           name="keyword"
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

    <div class="pc-search-results drawer-body grid grid-cols-2 bp1920px:grid-cols-3 auto-rows-max justify-between gap-x-4 lg:gap-x-8 bp1920px:gap-x-92px gap-y-3 bp1920px:gap-y-6 lg:gap-y-4 px-8 py-7">

    </div>

    <div class="drawer-footer gap-x-6 px-4">

    </div>
</div>
