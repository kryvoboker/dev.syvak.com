@extends('catalog.layouts.main')

@section('content')
    <x-catalog::common.breadcrumbs
        :breadcrumbs="$breadcrumbs"
    />

    <section class="failure-order section" id="failure-order">
        <div class="container">

        </div>
    </section>
@endsection
