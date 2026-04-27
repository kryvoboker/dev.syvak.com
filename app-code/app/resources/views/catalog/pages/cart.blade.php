@extends('catalog.layouts.main')

@section('content')
    <x-catalog::common.breadcrumbs
        :breadcrumbs="$breadcrumbs"
    />

    <section class="cart section" id="cart">
        <div class="container">

        </div>
    </section>
@endsection
