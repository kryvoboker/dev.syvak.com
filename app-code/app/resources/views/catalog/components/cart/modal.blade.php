<button class="hidden open-cart-modal-regular-trigger" type="button"></button>

<div class="overlay modal overlay-open:opacity-100 overlay-open:duration-300 hidden cart-modal max-md:p-0"
     id="cart-modal"
     role="dialog"
     tabindex="-1"
     data-cart-mode="regular">
    <div class="overlay-animation-target modal-dialog max-sm:max-w-none md:max-w-175 lg:max-w-218 2xl:max-w-[64rem]">
        <div class="modal-shadow modal-content max-sm:h-full max-sm:max-h-none bg-black border border-opacity-light-gray-40%">
            <div class="modal-header flex flex-col items-start gap-4 px-4 pt-4 pb-4 md:px-8 md:pt-6 lg:px-6 2xl:px-6 border-b border-b-opacity-light-gray-40%">
                <div class="hidden md:flex items-center justify-between gap-3 w-full pb-4 border-b border-b-opacity-light-gray-40%">
                    <button class="inline-flex items-center gap-4 text-sm lg:text-base 2xl:text-lg tracking-[0.02em]"
                            type="button"
                            aria-label="{{ __('catalog/default.aria_labels.close_mob_main_menu') }}"
                            data-overlay="#cart-modal">
                        <span class="icon-[mdi-light--arrow-left] custom-icon"></span>

                        <span>{{ __('catalog/default.cart.buttons.continue_shopping') }}</span>
                    </button>

                    <button class="btn btn-text btn-circle"
                            type="button"
                            aria-label="{{ __('catalog/default.aria_labels.close_mob_main_menu') }}"
                            data-overlay="#cart-modal">
                        <span class="icon-[iconamoon--close] custom-icon"></span>
                    </button>
                </div>

                <div class="flex items-center justify-between gap-2 w-full">
                    <h3 class="font-cormorant-garamond leading-0.9em uppercase text-32px md:text-44px lg:text-32px 2xl:text-64px tracking-normal lg:tracking-0.028em">
                        {{ __('catalog/default.cart.labels.cart') }}
                    </h3>

                    <button class="btn btn-text btn-circle md:hidden"
                            type="button"
                            aria-label="{{ __('catalog/default.aria_labels.close_mob_main_menu') }}"
                            data-overlay="#cart-modal">
                        <span class="icon-[iconamoon--close] custom-icon"></span>
                    </button>
                </div>
            </div>

            <div class="modal-body relative p-0 cart-modal-content overflow-y-auto" data-cart-modal-content="regular">
                @include('catalog.partials.cart.modal-items', [
                    'cart_data' => $cart_data ?? null,
                    'cart_mode' => 'regular',
                ])

                <x-catalog::common.loader class="cart-loader"/>
            </div>
        </div>
    </div>
</div>
