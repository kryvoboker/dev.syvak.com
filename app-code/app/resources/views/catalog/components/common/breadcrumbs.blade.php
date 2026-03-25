@props([
    'breadcrumbs' => [],
])

@if(!empty($breadcrumbs))
    <div class="container border-b border-b-opacity-light-gray-40%">
        <nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'breadcrumbs flex items-center']) }}>
            <ol class="breadcrumbs__list flex items-center gap-x-6 px-0 py-4">
                @foreach($breadcrumbs as $index => $breadcrumb_data)
                    @if($loop->first)
                        <li class="breadcrumbs__item">
                            <a class="" href="">
                                <span class="icon-[formkit--arrowleft] custom-icon"></span>
                            </a>
                        </li>
                    @endif

                    <li class="breadcrumbs__item" @if($loop->last) aria-current="page" @endif>
                        @if(!$loop->last && !empty($breadcrumb_data['url']))
                            <a class="breadcrumbs__link text-light-gray" href="{{ $breadcrumb_data['url'] }}">
                                {{ $breadcrumb_data['title'] }}
                            </a>
                        @else
                            <div class="breadcrumbs__current">
                                {{ $breadcrumb_data['title'] }}
                            </div>
                        @endif
                    </li>

                    @if(!$loop->last)
                        <li class="breadcrumbs__sep inline-block" aria-hidden="true">
                            /
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
@endif
