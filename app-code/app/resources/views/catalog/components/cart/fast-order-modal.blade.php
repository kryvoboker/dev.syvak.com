<button class="hidden open-cart-modal-fast-order-trigger" type="button"></button>

<div class="overlay modal overlay-open:opacity-100 overlay-open:duration-300 hidden cart-modal"
     id="fast-order-cart-modal"
     role="dialog"
     tabindex="-1"
     data-cart-mode="fast_order">
    <div class="overlay-animation-target modal-dialog max-sm:max-w-none lg:modal-dialog-lg 2xl:modal-dialog-xl">
        <div class="modal-shadow modal-content max-sm:h-full max-sm:max-h-none bg-black border border-opacity-light-gray-40%">
            <div class="modal-header flex flex-col gap-3 px-4 pt-4 md:px-8 md:pt-6 pb-4 border-b border-b-opacity-light-gray-40%">
                <div class="hidden md:flex items-center justify-between gap-3">
                    <a class="inline-flex items-center gap-2 text-sm tracking-[0.02em]"
                       href="{{ localized_route('localized.catalog.home') }}">
                        <span class="icon-[solar--alt-arrow-left-linear] custom-icon size-6 md:size-6"></span>
                        <span>{{ __('catalog/default.cart.buttons.continue_shopping') }}</span>
                    </a>

                    <button class="btn btn-text btn-circle"
                            type="button"
                            aria-label="{{ __('catalog/default.aria_labels.close_mob_main_menu') }}"
                            data-overlay="#fast-order-cart-modal">
                        <span class="icon-[iconamoon--close] custom-icon size-8"></span>
                    </button>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <h5 class="font-cormorant-garamond text-2xl md:text-44px leading-0.9em uppercase tracking-wide">
                        {{ __('catalog/default.cart.labels.fast_order') }}
                    </h5>

                    <button class="btn btn-text btn-circle md:hidden"
                            type="button"
                            aria-label="{{ __('catalog/default.aria_labels.close_mob_main_menu') }}"
                            data-overlay="#fast-order-cart-modal">
                        <span class="icon-[iconamoon--close] custom-icon size-8"></span>
                    </button>
                </div>
            </div>

            <div class="modal-body relative p-0 cart-modal-content" data-cart-modal-content="fast_order">
                @include('catalog.partials.cart.modal-items', [
                    'cart_data' => $cart_data ?? null,
                    'cart_mode' => 'fast_order',
                ])

                <x-catalog::common.loader class="cart-loader"/>
            </div>
        </div>
    </div>
</div>
