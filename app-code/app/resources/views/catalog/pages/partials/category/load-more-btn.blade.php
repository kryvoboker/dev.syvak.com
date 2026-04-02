@if($is_ajax_products_loading_enabled === true)
    <div class="flex justify-center mt-18px lg:mt-6 xl:mt-8">
        <button class="default-btn flex items-center justify-center gap-x-2 uppercase tracking-0.04em text-white duration-200 ease-in-out hover:text-light-gray"
                type="button">
            <span>
                {{ __('catalog/default.buttons.show_more') }}
            </span>

            <span class="icon-[solar--arrow-down-linear] custom-icon"></span>
        </button>
    </div>
@endif
