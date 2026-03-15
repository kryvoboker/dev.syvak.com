@extends('catalog.layouts.main')

@section('content')
    @foreach($carousel_modules_data ?? [] as $carousel_module_data)
        @include('carousel::storefront.main-carousel', ['carousel_module_data' => $carousel_module_data])
    @endforeach

    @foreach($products_carousel_modules_data ?? [] as $products_carousel_module_data)
        @include('productscarousel::storefront.products-carousel', [
            'products_carousel_module_data' => $products_carousel_module_data,
        ])
    @endforeach
@endsection
