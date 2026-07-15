@php
    $payment_data = is_array($payment_upon_delivery_checkout_data ?? null) ? $payment_upon_delivery_checkout_data : [];
@endphp

@if(($payment_data['is_available'] ?? false) === true)
    <label class="flex items-center gap-3 py-2 text-sm text-white md:text-lg"
           data-checkout-payment-method-option="{{ $payment_data['payment_method'] }}">
        <input class="radio radio-sm border border-white rounded-none" data-checkout-payment-method-input name="payment_method" type="radio" value="{{ $payment_data['payment_method'] }}" required @checked(($selected_payment_method ?? '') === $payment_data['payment_method'])>
        <span>{{ __($payment_data['label_translation_key']) }}</span>
    </label>
@endif
