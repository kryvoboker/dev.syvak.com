<label class="flex items-center gap-3 border-b border-opacity-light-gray-40% py-2 text-sm text-white md:text-lg"
       data-checkout-delivery-method-option="nova_poshta">
    <input class="radio radio-sm border border-white rounded-none" data-checkout-delivery-method-input name="delivery_method" type="radio" value="nova_poshta" @checked(($selected_delivery_method ?? '') === 'nova_poshta')>
    <span>{{ __('storefront/pages/checkout.delivery_methods.nova_poshta') }}</span>
</label>

<label class="flex items-center gap-3 border-b border-opacity-light-gray-40% py-2 text-sm text-white md:text-lg"
       data-checkout-delivery-method-option="nova_poshta_courier">
    <input class="radio radio-sm border border-white rounded-none" data-checkout-delivery-method-input name="delivery_method" type="radio" value="nova_poshta_courier" @checked(($selected_delivery_method ?? '') === 'nova_poshta_courier')>
    <span>{{ __('storefront/pages/checkout.delivery_methods.nova_poshta_courier') }}</span>
</label>

<label class="flex items-center gap-3 border-b border-opacity-light-gray-40% py-2 text-sm text-white md:text-lg"
       data-checkout-delivery-method-option="nova_poshta_poshtomat">
    <input class="radio radio-sm border border-white rounded-none" data-checkout-delivery-method-input name="delivery_method" type="radio" value="nova_poshta_poshtomat" @checked(($selected_delivery_method ?? '') === 'nova_poshta_poshtomat')>
    <span>{{ __('storefront/pages/checkout.delivery_methods.nova_poshta_poshtomat') }}</span>
</label>
