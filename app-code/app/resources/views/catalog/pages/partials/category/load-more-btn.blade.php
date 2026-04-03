@if($is_ajax_products_loading_enabled === true && $is_has_more_pages === true)
    <div class="flex justify-center mt-18px lg:mt-6 xl:mt-8">
        <button class="load-more-prods-btn default-btn flex items-center justify-center gap-x-2 uppercase tracking-0.04em
                       text-white duration-200 ease-in-out hover:text-light-gray"
                type="button">
            <span>
                {{ __('catalog/default.buttons.show_more') }}
            </span>

            <span class="arrow-down-icon icon-[solar--arrow-down-linear] custom-icon"></span>
            <span class="rounded-arrow-icon hidden icon-[subway--round-arrow-1] animate-spin custom-icon"></span>
        </button>
    </div>
@endif
