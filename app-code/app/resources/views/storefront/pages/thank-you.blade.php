@php
    $thank_you_data = $thank_you_data ?? [];
    $customer = $thank_you_data['customer'] ?? [];
    $delivery = $thank_you_data['delivery'] ?? [];
    $products = $thank_you_data['products'] ?? [];
    $summary = $thank_you_data['summary'] ?? [];
    $visible_products = array_slice($products, 0, 1);
    $hidden_products = array_slice($products, 1);
@endphp

@extends('storefront.layouts.main')

@if(($order_found ?? false) === false)
    @section('title', strip_tags(__('storefront/pages/thank-you.not_found.heading')))
@else
    @section('title', strip_tags(__('storefront/pages/thank-you.heading')))
@endif


@section('content')
    <x-storefront::common.breadcrumbs :breadcrumbs="$breadcrumbs ?? []"/>

    <section class="thank-you section"
             id="thank-you" data-thank-you-page>
        <div class="container relative">
            @if(($order_found ?? false) === false)
                <div class="flex min-h-120 max-w-3xl flex-col justify-center gap-6 text-white z-1">
                    <h1 class="section-title">
                        {{ __('storefront/pages/thank-you.not_found.heading') }}
                    </h1>
                    <p class="text-lg text-light-gray md:text-2xl">
                        {{ __('storefront/pages/thank-you.not_found.message', ['number' => $requested_order_number ?? '—']) }}
                    </p>
                </div>
            @else
                <h1 class="text-white">{{ __('storefront/pages/thank-you.heading') }}</h1>

                <div class="relative mt-10 grid gap-12 lg:mt-16 lg:grid-cols-2 lg:gap-x-16 2xl:gap-x-24 z-1">
                    <div class="min-w-0">
                        <div class="flex flex-col gap-6">
                            <p class="text-lg text-light-gray md:text-2xl">
                                {{ __('storefront/pages/thank-you.labels.order_number', ['number' => $thank_you_data['order_id'] ?? '—']) }}
                            </p>

                            <div class="grid gap-4 text-base text-white md:grid-cols-2 md:text-lg">
                                <div class="flex min-w-0 flex-col gap-2">
                                    <span class="wrap-break-word">/ {{ $customer['name'] ?? '—' }} /</span>
                                    <span class="wrap-break-word">/ {{ $customer['phone'] ?? '—' }} /</span>
                                </div>
                                <div class="flex min-w-0 flex-col gap-2 md:items-end md:text-right">
                                    <span class="break-all">/ {{ $customer['email'] ?? '—' }} /</span>
                                    <span class="wrap-break-word">/ {{ $delivery['address'] ?? '—' }} /</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8" data-thank-you-products>
                            @foreach($visible_products as $product)
                                @include('storefront.partials.thank-you.product-item', ['product' => $product])
                            @endforeach

                            @if($hidden_products !== [])
                                <div class="accordion group" data-thank-you-products-accordion>
                                    <div class="accordion-item">
                                        <div
                                            class="accordion-content hidden overflow-hidden transition-[height]"
                                            id="thank-you-extra-products"
                                            role="region"
                                            aria-label="{{ __('storefront/pages/thank-you.labels.additional_products') }}"
                                        >
                                            @foreach($hidden_products as $product)
                                                @include('storefront.partials.thank-you.product-item', ['product' => $product])
                                            @endforeach
                                        </div>

                                        <button
                                            class="accordion-toggle inline-flex min-h-11 items-center gap-2 py-3 text-sm text-white transition-opacity hover:opacity-70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                                            type="button"
                                            aria-expanded="false"
                                            aria-controls="thank-you-extra-products"
                                        >
                                        <span class="group-[.active]:hidden">
                                            {{ __('storefront/pages/thank-you.buttons.show_all_products', ['count' => count($products)]) }}
                                        </span>
                                            <span class="hidden group-[.active]:inline">
                                            {{ __('storefront/pages/thank-you.buttons.hide_products') }}
                                        </span>
                                            <span class="icon-[ep--arrow-down] size-5 transition-transform group-[.active]:rotate-180" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="min-w-0 lg:pt-16">
                        <dl class="flex flex-col gap-5 text-base text-white md:text-lg">
                            <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] md:gap-6">
                                <dt>{{ __('storefront/pages/thank-you.labels.payment_method') }}</dt>
                                <dd class="wrap-break-word">{{ $summary['payment_method'] ?? '—' }}</dd>
                            </div>
                            <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] md:gap-6">
                                <dt>{{ __('storefront/pages/thank-you.labels.delivery_method') }}</dt>
                                <dd class="wrap-break-word">{{ $summary['delivery_method'] ?? $delivery['method'] ?? '—' }}</dd>
                            </div>
                            <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] md:gap-6">
                                <dt>{{ __('storefront/pages/thank-you.labels.delivery_address') }}</dt>
                                <dd class="wrap-break-word">{{ $summary['delivery_address'] ?? $delivery['address'] ?? '—' }}</dd>
                            </div>
                            <div class="mt-2 grid gap-2 border-t border-light-gray/40 pt-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] md:gap-6">
                                <dt>{{ __('storefront/pages/thank-you.labels.subtotal') }}</dt>
                                <dd class="wrap-break-word">{{ $summary['subtotal'] ?? '—' }}</dd>
                            </div>
                            @if(($summary['promo_code_discount'] ?? null) !== null)
                                <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] md:gap-6">
                                    <dt>{{ __('storefront/pages/thank-you.labels.promo_code_discount') }}</dt>
                                    <dd class="wrap-break-word">-{{ $summary['promo_code_discount'] }}</dd>
                                </div>
                            @endif
                            <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] md:gap-6">
                                <dt>{{ __('storefront/pages/thank-you.labels.packaging') }}</dt>
                                <dd class="wrap-break-word">{{ $summary['packaging'] ?? '—' }}</dd>
                            </div>
                            <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] md:gap-6">
                                <dt>{{ __('storefront/pages/thank-you.labels.delivery_cost') }}</dt>
                                <dd class="wrap-break-word">{{ $summary['delivery_cost'] ?? '—' }}</dd>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-light-gray/40 pt-5 text-xl font-bold md:text-2xl">
                                <dt>{{ __('storefront/pages/thank-you.labels.total') }}</dt>
                                <dd class="whitespace-nowrap">{{ $summary['total'] ?? '—' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-8 flex max-w-xl flex-col gap-4 text-base text-light-gray md:text-lg">
                            <p>{{ $summary['notes'] ?? __('storefront/pages/thank-you.fallbacks.notes') }}</p>
                            <p>{{ __('storefront/pages/thank-you.fallbacks.tracking') }}</p>
                            <p>{{ __('storefront/pages/thank-you.fallbacks.contact') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <x-storefront::common.img
                class="object-contain pointer-events-none absolute left-0 bottom-0 opacity-25 z-0"
                :urls_data="$background_image_data['urls']"
                :size="$background_image_data['width']"
                :max-density="3"
                sizes="100vw"
                width="{{ $background_image_data['width'] }}"
                height="{{ $background_image_data['height'] }}"
                aria-hidden="true"
                alt=""
            />
        </div>
    </section>
@endsection
