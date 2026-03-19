<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ $this->getIntroTitle() }}
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                {{ $this->getIntroDescription() }}
            </p>

            @if(! empty($this->getPracticalExamples()))
                <h3 class="mt-5 text-sm font-semibold text-gray-900">
                    {{ __('admin/wiki.common.examples_title') }}
                </h3>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-600">
                    @foreach($this->getPracticalExamples() as $example)
                        <li>{{ $example }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        @foreach($this->getWikiSections() as $section)
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ $section['title'] }}</h2>
                <p class="mt-2 text-sm text-gray-600">{{ $section['description'] }}</p>

                @if(filled($section['screenshot_relative']))
                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        @if(filled($section['screenshot_url']))
                            <img src="{{ $section['screenshot_url'] }}"
                                 alt="{{ $section['title'] }}"
                                 class="w-full rounded-md border border-gray-200 bg-white shadow-sm"
                            >
                            @if(filled($section['screenshot_description']))
                                <p class="mt-2 text-xs text-gray-500">
                                    {{ $section['screenshot_description'] }}
                                </p>
                            @endif
                        @else
                            <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                                <p class="font-medium">{{ __('admin/wiki.common.screenshot_missing_title') }}</p>
                                <p class="mt-1">{{ __('admin/wiki.common.screenshot_missing_description') }}</p>
                                <p class="mt-1 font-mono text-xs text-amber-800">{{ $section['screenshot_relative'] }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                @if(! empty($section['items']))
                    <ul class="mt-4 list-disc space-y-1 pl-5 text-sm text-gray-700">
                        @foreach($section['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
