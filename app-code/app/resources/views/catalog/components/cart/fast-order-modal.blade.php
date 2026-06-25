<button class="hidden open-cart-modal-fast-order-trigger" type="button"></button>

<div class="overlay modal overlay-open:opacity-100 overlay-open:duration-300 hidden cart-modal max-md:p-0"
     id="fast-order-cart-modal"
     role="dialog"
     tabindex="-1"
     data-cart-mode="fast_order">
    <div class="overlay-animation-target modal-dialog max-sm:max-w-none md:max-w-175 lg:max-w-218 2xl:max-w-[64rem]">
        <div class="modal-shadow modal-content max-sm:h-full max-sm:max-h-none bg-black border border-opacity-light-gray-40%">
            <div class="modal-header flex flex-col items-start gap-4 px-4 pt-4 pb-4 md:px-8 md:pt-6 lg:px-6 2xl:px-6 border-b border-b-opacity-light-gray-40%">
                <div class="hidden md:flex items-center justify-between gap-3 w-full pb-4 border-b border-b-opacity-light-gray-40%">
                    <button class="inline-flex items-center gap-4 text-sm lg:text-base 2xl:text-lg tracking-[0.02em]"
                            type="button"
                            aria-label="{{ __('catalog/default.aria_labels.close_mob_main_menu') }}"
                            data-overlay="#fast-order-cart-modal">
                        <span class="icon-[mdi-light--arrow-left] custom-icon"></span>

                        <span>{{ __('catalog/default.cart.buttons.continue_shopping') }}</span>
                    </button>

                    <button class="btn btn-text btn-circle"
                            type="button"
                            aria-label="{{ __('catalog/default.aria_labels.close_mob_main_menu') }}"
                            data-overlay="#fast-order-cart-modal">
                        <span class="icon-[iconamoon--close] custom-icon"></span>
                    </button>
                </div>

                <div class="flex items-center justify-between gap-2 w-full">
                    <h3 class="font-cormorant-garamond leading-0.9em uppercase text-32px md:text-44px lg:text-32px 2xl:text-64px tracking-normal lg:tracking-0.028em">
                        {{ __('catalog/default.cart.labels.fast_order') }}
                    </h3>

                    <button class="btn btn-text btn-circle md:hidden"
                            type="button"
                            aria-label="{{ __('catalog/default.aria_labels.close_mob_main_menu') }}"
                            data-overlay="#fast-order-cart-modal">
                        <span class="icon-[iconamoon--close] custom-icon"></span>
                    </button>
                </div>
            </div>

            <div class="modal-body relative p-0 cart-modal-content overflow-y-auto" data-cart-modal-content="fast_order">
                @include('catalog.partials.cart.modal-items', [
                    'cart_data' => $cart_data ?? null,
                    'cart_mode' => 'fast_order',
                ])
            </div>

            <div class="border-t border-t-opacity-light-gray-40% px-4 py-4 md:px-8 md:py-6 lg:px-6 2xl:px-6 {{ ($cart_data['is_empty'] ?? true) === true ? 'hidden' : '' }}"
                 data-fast-order-form-wrapper>
                <form class="flex flex-col gap-4"
                      action="{{ localized_route('localized.catalog.order-confirm.store') }}"
                      data-fast-order-form
                      method="post"
                      novalidate>
                    @csrf

                    <input name="cart_mode" type="hidden" value="fast_order"/>
                    <input name="payment_method" type="hidden" value="cash_on_delivery"/>

                    <div class="flex flex-col gap-1">
                        <label class="flex flex-col gap-1 text-sm text-white" for="fast-order-first-name">
                            <span>{{ __('catalog/default.cart.labels.first_name') }}*</span>
                            <input class="border-0 border-b border-b-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm md:text-base"
                                   data-error-min="{{ __('catalog/default.cart.validation.first_name_min', ['min' => 2]) }}"
                                   data-error-required="{{ __('catalog/default.cart.validation.first_name_required') }}"
                                   data-order-first-name
                                   id="fast-order-first-name"
                                   name="first_name"
                                   autocomplete="given-name"
                                   aria-describedby="fast-order-first-name-error"
                                   placeholder="{{ __('catalog/default.cart.labels.first_name') }}"
                                   required
                                   type="text"/>
                        </label>

                        <span class="_error text-xs md:text-sm"
                              aria-live="polite"
                              data-order-field-error="first_name"
                              id="fast-order-first-name-error"></span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="flex flex-col gap-1 text-sm text-white" for="fast-order-last-name">
                            <span>{{ __('catalog/default.cart.labels.last_name') }}*</span>
                            <input class="border-0 border-b border-b-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm md:text-base"
                                   data-error-min="{{ __('catalog/default.cart.validation.last_name_min', ['min' => 2]) }}"
                                   data-error-required="{{ __('catalog/default.cart.validation.last_name_required') }}"
                                   data-order-last-name
                                   id="fast-order-last-name"
                                   name="last_name"
                                   autocomplete="family-name"
                                   aria-describedby="fast-order-last-name-error"
                                   placeholder="{{ __('catalog/default.cart.labels.last_name') }}"
                                   required
                                   type="text"/>
                        </label>

                        <span class="_error text-xs md:text-sm"
                              aria-live="polite"
                              data-order-field-error="last_name"
                              id="fast-order-last-name-error"></span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="flex flex-col gap-1 text-sm text-white" for="fast-order-phone">
                            <span>{{ __('catalog/default.cart.labels.phone') }}*</span>
                            <input class="border-0 border-b border-b-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm md:text-base"
                                   data-error-min="{{ __('catalog/default.cart.validation.phone_min', ['min' => 10]) }}"
                                   data-error-required="{{ __('catalog/default.cart.validation.phone_required') }}"
                                   data-order-phone
                                   id="fast-order-phone"
                                   name="phone"
                                   autocomplete="tel"
                                   inputmode="tel"
                                   aria-describedby="fast-order-phone-error"
                                   placeholder="{{ __('catalog/default.cart.labels.phone') }}"
                                   required
                                   type="tel"/>
                        </label>

                        <span class="_error text-xs md:text-sm"
                              aria-live="polite"
                              data-order-field-error="phone"
                              id="fast-order-phone-error"></span>
                    </div>

                    <button class="white-btn default-btn w-full text-lg"
                            data-submit-fast-order
                            type="submit">
                        {{ __('catalog/default.cart.buttons.submit_fast_order') }}
                    </button>
                </form>
            </div>

            <x-catalog::common.loader class="cart-loader"/>
        </div>
    </div>
</div>
