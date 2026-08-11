@php
    $images = $images ?? [];
    $buttons = $buttons ?? [];
    $support = $support ?? [];
    $payment_methods = $payment_methods ?? [];
    $recovery = $recovery ?? [];
    $has_alternative_payment = data_get($buttons, 'alternative_payment.enabled', false)
        && data_get($buttons, 'available_payment_methods.enabled', false)
        && filled($payment_methods);
@endphp

@extends('catalog.layouts.main')

@section('content')
    <section class="section failure-order min-h-[calc(100vh-10rem)] overflow-hidden py-10 md:py-16" id="failure-order" data-failure-page
             data-retry-url="{{ $recovery['retry_url'] ?? '' }}"
             data-payment-url="{{ $recovery['payment_url'] ?? '' }}"
             data-retry-payment-method="{{ $recovery['payment_method'] ?? '' }}"
             data-retry-attempts="{{ $recovery['retry_count'] ?? 0 }}">
        <div class="container relative flex min-h-[inherit] flex-col items-center justify-center gap-5 text-center md:gap-5">
            <div class="pointer-events-none absolute inset-0 z-0 hidden items-center justify-between gap-4 opacity-50 2xl:flex">
                @foreach($images as $image)
                    <x-catalog::common.img
                        @class([$image['custom_css_classes'] ?? '', 'max-h-[38rem] max-w-[34%] object-contain'])
                        :urls_data="$image['urls']"
                        :size="$image['width']"
                        :max-density="3"
                        sizes="34vw"
                        width="{{ $image['width'] }}"
                        height="{{ $image['height'] }}"
                        alt=""
                        aria-hidden="true"
                    />
                @endforeach
            </div>

            <div class="relative z-10 flex w-full max-w-154.5 flex-col items-center gap-5 px-2 md:px-0">
                <h1 class="w-full max-w-55.5 font-cormorant-garamond text-32px font-normal uppercase leading-[.9] text-white md:max-w-full md:text-44px lg:max-w-103.5 lg:text-[60px] 2xl:max-w-154.5 2xl:text-90px">
                    {{ $title ?? __('catalog/failure.fallbacks.title') }}
                </h1>

                @if(filled($description ?? null))
                    <p class="w-full whitespace-pre-line font-inter text-sm font-normal leading-[1.4] tracking-[.02em] text-white lg:text-base 2xl:text-lg">
                        {{ $description }}
                    </p>
                @endif

                <div class="flex w-full flex-col items-center gap-5">
                    {{-- Temporary visual preview: restore the recovery checks after styling. --}}
{{--                    @if(data_get($buttons, 'retry.enabled', false) && filled($recovery['payment_method'] ?? null))--}}
                    @if(true)
                        <button type="button"
                                class="btn w-full rounded-none border-0 bg-white px-8 py-4 font-inter text-lg font-normal uppercase leading-[1.4] text-black hover:bg-white/90 2xl:text-2xl {{ data_get($buttons, 'retry.custom_css_classes', '') }}"
                                data-failure-retry>
                            {{ data_get($buttons, 'retry.label', __('catalog/failure.buttons.retry')) }}
                        </button>
                    @endif

                    @if($has_alternative_payment)
                        <div id="failure-payment-methods" class="accordion w-full text-start" data-failure-payment-accordion>
                            <div class="accordion-item border border-white p-0">
                                <button type="button"
                                        class="accordion-toggle flex w-full items-center justify-between gap-3 rounded-none px-8 py-4 text-start font-inter text-lg font-normal leading-[1.4] text-white 2xl:text-2xl {{ data_get($buttons, 'alternative_payment.custom_css_classes', '') }}"
                                        data-failure-payment-toggle
                                        aria-expanded="false"
                                        aria-controls="failure-payment-methods-content">
                                    {{ data_get($buttons, 'alternative_payment.label', __('catalog/failure.buttons.alternative_payment')) }}
                                    <span class="icon-[solar--alt-arrow-down-linear] shrink-0" aria-hidden="true"></span>
                                </button>
                                <div id="failure-payment-methods-content" class="accordion-content hidden overflow-hidden" role="region">
                                    <div class="flex flex-col gap-2 px-4 pb-4">
                                        @foreach($payment_methods as $payment_method)
                                            <button type="button"
                                                    class="btn btn-ghost justify-start rounded-none text-start font-inter"
                                                    data-failure-payment-method="{{ $payment_method['payment_method'] }}">
                                                {{ $payment_method['payment_name'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="hidden w-full rounded-xl border border-light-red bg-light-red/10 p-3 text-sm text-light-red" role="alert" data-failure-error></div>
                </div>
            </div>

            <div class="relative z-10 flex w-full max-w-154.5 flex-col gap-5 border border-light-gray p-4 text-start md:px-8 md:py-4">
                <div class="flex flex-col gap-2">
                    <h2 class="font-cormorant-garamond text-lg font-bold uppercase leading-none text-white md:text-2xl">{{ data_get($support, 'working_hours.title', __('catalog/failure.support.working_hours')) }}</h2>
                    @if(filled(data_get($support, 'working_hours.description')))
                        <p class="font-inter text-sm font-normal leading-[1.4] tracking-[.02em] text-white lg:text-base 2xl:text-lg">{{ data_get($support, 'working_hours.description') }}</p>
                    @endif
                    @if(filled(data_get($support, 'working_hours.content')))
                        <p class="whitespace-pre-line font-inter text-sm font-bold leading-[1.4] tracking-[.02em] text-white lg:text-base 2xl:text-lg">{{ data_get($support, 'working_hours.content') }}</p>
                    @endif
                </div>

                <div class="flex flex-col gap-2">
                    <h2 class="font-cormorant-garamond text-lg font-bold uppercase leading-none text-white md:text-2xl">{{ __('catalog/failure.support.contacts') }}</h2>
                    @foreach(data_get($support, 'phones', []) as $phone)
                        <a class="font-inter text-lg leading-[1.4] text-white transition-opacity hover:opacity-70 {{ $phone['custom_css_classes'] ?? '' }}"
                           href="tel:{{ $phone['value'] }}">{{ $phone['value'] }}</a>
                    @endforeach
                    @foreach(data_get($support, 'emails', []) as $email)
                        <a class="font-inter text-lg leading-[1.4] text-white transition-opacity hover:opacity-70 {{ $email['custom_css_classes'] ?? '' }}"
                           href="mailto:{{ $email['value'] }}">{{ $email['value'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
