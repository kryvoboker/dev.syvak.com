@extends('catalog.layouts.main')

@section('content')
    <x-catalog::common.breadcrumbs
        :breadcrumbs="$breadcrumbs"
        class="mb-6 md:mb-7 lg:mb-8"
    />

    <section class="category-products-list-section border-b border-opacity-light-gray-40% py-10 md:py-12 lg:py-14 2xl:py-16"
             aria-label="{{ __('catalog/default.aria_labels.category_products_list') }}">
        <div class="container">
            <div class="mb-6 flex items-end justify-between gap-4 md:mb-7 md:gap-6 lg:mb-8">
                <h1 class="section-title">
                    {{ $category_title }}
                </h1>

                <div class="flex items-center gap-x-1 md:gap-x-2 lg:gap-x-3">
                    @include('catalog.pages.partials.category.products-filter-btn')

                    @include('catalog.pages.partials.category.products-sort')
                </div>
            </div>

            @include('catalog.pages.partials.category.products-list')
        </div>
    </section>

    @include('catalog.pages.partials.category.products-filter-content')
@endsection
