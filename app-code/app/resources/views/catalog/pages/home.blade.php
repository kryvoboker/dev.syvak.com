@extends('catalog.layouts.main')

@section('content')
    @foreach($carousel_modules_data ?? [] as $carousel_module_data)
        @include('carousel::storefront.main-carousel', ['carousel_module_data' => $carousel_module_data])
    @endforeach
@endsection
