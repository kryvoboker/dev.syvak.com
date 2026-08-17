<label class="flex items-center gap-3 border-b border-opacity-light-gray-40% py-2 text-sm text-white md:text-lg"
       data-checkout-delivery-method-option="ukr_poshta">
    <input class="radio radio-sm border border-white rounded-none" data-checkout-delivery-method-input name="delivery_method" type="radio" value="ukr_poshta" @checked(($selected_delivery_method ?? '') === 'ukr_poshta')>
    <span>{{ __('storefront/pages/checkout.delivery_methods.ukr_poshta') }}</span>
</label>
