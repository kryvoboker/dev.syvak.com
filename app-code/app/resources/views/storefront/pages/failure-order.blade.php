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

@extends('storefront.layouts.main')

@section('title', strip_tags($title ?? __('storefront/failure.fallbacks.title')))

@section('content')
    <section class="section failure-order"
             id="failure-order" data-failure-page
             data-retry-url="{{ $recovery['retry_url'] ?? '' }}"
             data-payment-url="{{ $recovery['payment_url'] ?? '' }}"
             data-retry-payment-method="{{ $recovery['payment_method'] ?? '' }}"
             data-retry-attempts="{{ $recovery['retry_count'] ?? 0 }}">
        <div class="container relative">
            <div class="relative max-w-200 z-1 mx-auto">
                <h1 class="section-title text-center mb-4 md:mb-5">
                    {{ $title ?? __('storefront/failure.fallbacks.title') }}
                </h1>

                <div class="flex flex-col items-center gap-y-6 md:gap-y-8">
                    <div class="flex w-full flex-col items-center gap-5 px-2 md:px-0">
                        @if(filled($description ?? null))
                            <p class="w-full whitespace-pre-line font-inter text-sm font-normal text-center leading-[1.4] tracking-[.02em] text-white lg:text-base 2xl:text-lg">{{--
                              --}}{{ $description }}{{--
                              --}}
                            </p>
                        @endif

                        <div class="flex w-full flex-col items-center gap-5">
                            {{-- Temporary visual preview: restore the recovery checks after styling. --}}
{{--                            @if(true)--}}
                            @if(data_get($buttons, 'retry.enabled', false) && filled($recovery['payment_method'] ?? null))
                                <button type="button"
                                        class="btn w-full {{ data_get($buttons, 'retry.custom_css_classes', '') }} py-4!"
                                        data-failure-retry>
                                    {{ data_get($buttons, 'retry.label', __('storefront/failure.buttons.retry')) }}
                                </button>
                            @endif

                            @if($has_alternative_payment)
                                <div id="failure-payment-methods" class="accordion w-full text-start" data-failure-payment-accordion>
                                    <div class="accordion-item border border-white p-0">
                                        <button type="button"
                                                class="btn btn-black accordion-toggle w-full normal-case! bg-transparent {{ data_get($buttons, 'alternative_payment.custom_css_classes', '') }} py-4!"
                                                data-failure-payment-toggle
                                                aria-expanded="false"
                                                aria-controls="failure-payment-methods-content">
                                            {{ data_get($buttons, 'alternative_payment.label', __('storefront/failure.buttons.alternative_payment')) }}
                                        </button>
                                        <div id="failure-payment-methods-content" class="accordion-content hidden overflow-hidden" role="region">
                                            <div class="flex flex-col gap-2 p-4">
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

                    @if(filled(data_get($support, 'working_hours.title', __('storefront/failure.support.working_hours'))) && filled(data_get($support, 'working_hours.content')))
                        <x-storefront::contacts.working-hours
                            :working_hours="data_get($support, 'working_hours', [])"
                            :fallback_title="__('storefront/failure.support.working_hours')"
                            :phones="data_get($support, 'phones', [])"
                            :emails="data_get($support, 'emails', [])"
                        />
                    @endif
                </div>
            </div>

            @if(!empty($images))
                <div class="flex items-end justify-between gap-x-2 w-full absolute -bottom-10 left-0 pointer-events-none z-0">
                    @foreach($images as $image)
                        <x-storefront::common.img
                            @class([
                                $image['custom_css_classes'] ?? '',
                                'object-contain',
                            ])
                            :urls_data="$image['urls']"
                            :size="$image['width']"
                            :max-density="3"
                            sizes="(max-width: 768px) 56vw, (max-width: 1024px) 48vw, 34vw"
                            width="{{ $image['width'] }}"
                            height="{{ $image['height'] }}"
                            alt=""
                            aria-hidden="true"
                        />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
