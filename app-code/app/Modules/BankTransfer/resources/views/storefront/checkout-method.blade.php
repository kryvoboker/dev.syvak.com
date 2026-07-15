@php
    $payment_data = is_array($bank_transfer_checkout_data ?? null) ? $bank_transfer_checkout_data : [];
@endphp

@if(($payment_data['is_available'] ?? false) === true)
    <label class="flex items-center gap-3 py-2 text-sm text-white md:text-lg"
           data-checkout-payment-method-option="{{ $payment_data['payment_method'] }}">
        <input class="radio radio-sm border border-white rounded-none" data-checkout-payment-method-input name="payment_method" type="radio" value="{{ $payment_data['payment_method'] }}" required @checked(($selected_payment_method ?? '') === $payment_data['payment_method'])>
        <span>{{ $payment_data['payment_name'] ?? __($payment_data['label_translation_key']) }}</span>
    </label>

    @if(filled($payment_data['payment_information'] ?? null))
        <div @class([
            'alert rounded alert-info text-sm md:text-base',
            'hidden' => ($selected_payment_method ?? '') !== $payment_data['payment_method'],
        ])
             data-checkout-payment-information="{{ $payment_data['payment_method'] }}"
             @if(($selected_payment_method ?? '') !== $payment_data['payment_method']) hidden @endif
             aria-hidden="{{ ($selected_payment_method ?? '') !== $payment_data['payment_method'] ? 'true' : 'false' }}">
            {{ $payment_data['payment_information'] }}
        </div>
    @endif
@endif
