@extends('storefront.layouts.main')

@section('content')
    <x-storefront::common.breadcrumbs
        :breadcrumbs="$breadcrumbs"
    />

    <section class="category section" id="category"
             aria-label="{{ __('storefront/default.aria_labels.category_products_list') }}">
        <div class="container">
            <h1 class="section-title">
                {{ $category_title }}
            </h1>

            <div class="grid grid-cols-2 items-center gap-x-4 md:gap-x-0 max-w-500px ms-auto">
                @include('storefront.pages.partials.category.filter-btn')

                @if($sort_options !== [])
                    @include('storefront.pages.partials.category.sort')
                @endif
            </div>

            @include('storefront.pages.partials.category.category-content-container')
        </div>
    </section>

    @include('storefront.pages.partials.category.filter-content')

    <script>
        window.app_params = {
            ...(window.app_params ?? {}),
            ...@js([
                'load_more_products_ajax_url' => $load_more_products_ajax_url,
            ])
        };
    </script>
@endsection
