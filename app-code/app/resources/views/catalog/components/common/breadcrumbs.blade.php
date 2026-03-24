@props([
    'breadcrumbs' => [],
])

@if(!empty($breadcrumbs))
    <div class="container">
        <nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'breadcrumbs']) }}>
            <ol class="breadcrumbs__list flex items-center gap-x-2">
                @foreach($breadcrumbs as $index => $breadcrumb_data)
                    @php
                        $is_last = $index === count($breadcrumbs) - 1;
                    @endphp

                    <li class="breadcrumbs__item" @if($is_last) aria-current="page" @endif>
                        @if(!$is_last && !empty($breadcrumb_data['url']))
                            <a class="breadcrumbs__link" href="{{ $breadcrumb_data['url'] }}">
                                {{ $breadcrumb_data['title'] }}
                            </a>
                        @else
                            <span class="breadcrumbs__current">
                            {{ $breadcrumb_data['title'] }}
                        </span>
                        @endif
                    </li>

                    @if(!$is_last)
                        <li class="breadcrumbs__sep inline-block" aria-hidden="true">
                            /
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
@endif
