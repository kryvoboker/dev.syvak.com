<div class="language-switcher">
    @foreach(config('app.locales') as $locale)
        <a href="{{ localizedRoute(Route::currentRouteName(), compact('locale')) }}"
           class="{{ app()->getLocale() === $locale ? 'active' : '' }}">
            {{ Str::upper($locale) }}
        </a>
    @endforeach
</div>
