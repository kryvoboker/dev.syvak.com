@php
    $not_found_data = $not_found_data ?? [];
    $not_found_link = $not_found_data['link'] ?? [];
    $not_found_images = $not_found_data['images'] ?? [];
@endphp

@extends('catalog.layouts.main')

@section('content')
    <section class="section not-found-page"
             id="not-found-page">
        <div class="container">
            <div class="flex flex-col items-center gap-y-6 text-center md:gap-y-8 mx-auto">
                <h1 aria-hidden="true">
                    404
                </h1>

                <div class="flex max-w-2xl flex-col items-center gap-4">
                    <h2>
                        {{ $not_found_data['title'] ?? __('http-statuses.404') }}
                    </h2>

                    @if(filled($not_found_data['description'] ?? null))
                        <p class="max-w-xl text-balance text-base text-light-gray md:text-lg lg:text-xl">
                            {{ $not_found_data['description'] }}
                        </p>
                    @endif
                </div>

                <a class="relative default-btn black-btn text-xl xl:text-2xl uppercase leading-[6.5] rounded-full"
                   href="{{ $not_found_link['url'] ?? localized_route('catalog.home') }}">
                    {{ $not_found_link['label'] ?? __('catalog/pages/not-found.buttons.go_home') }}
                </a>
            </div>

            @if(!empty($not_found_images))
                <div class="flex items-end justify-between gap-x-2 xl:-mt-68">
                    @foreach($not_found_images as $image)
                        <x-catalog::common.img
                            @class([
                                'object-contain',
                                'opacity-50' => !$loop->first,
                                'hidden md:block' => $loop->first,
                                'hidden xl:block' => !$loop->first,
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
