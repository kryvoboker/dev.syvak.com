@extends('catalog.layouts.main')

@section('content')
    <x-catalog::common.breadcrumbs
        :breadcrumbs="$breadcrumbs"
    />

    <section class="category-section section"
             aria-label="{{ __('catalog/default.aria_labels.category_products_list') }}">
        <div class="container">
            <h1 class="section-title">
                {{ $category_title }}
            </h1>

            <div class="grid grid-cols-2 items-center gap-x-4 md:gap-x-0 max-w-500px ms-auto">
                @include('catalog.pages.partials.category.products-filter-btn')

                @include('catalog.pages.partials.category.products-sort')
            </div>

            @include('catalog.pages.partials.category.products-list')
        </div>
    </section>

    @include('catalog.pages.partials.category.products-filter-content')
@endsection
