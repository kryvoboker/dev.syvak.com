<div class="overlay overlay-open:translate-x-0 drawer drawer-start category-filter-drawer bg-black     Hidden     open opened    p-4"
     id="category-filter-drawer"
     role="dialog"
     tabindex="-1">
    <div class="drawer-header items-center justify-between p-0">
        <div class="text-2xl uppercase">
            Фільтр
        </div>

        <button type="button"
                class="btn btn-text btn-circle ms-auto"
                aria-expanded="true"
                aria-controls="Close category filter drawer"
                data-overlay="#category-filter-drawer">
            <span class="icon-[iconamoon--close] custom-icon"></span>
        </button>
    </div>

    <div class="drawer-body p-0">
        <div class="category-filter__total-results text-light-gray font-light text-lg tracking-0.04em mt-2 mb-4">
            Знайдено 00 товарів
        </div>

        <div class="flex items-center justify-between gap-x-5">
            <input type="text"
                   id="category-filter-steps-input-to"
                   data-start-max="3000">

            <span>
                <svg width="19" height="1" viewBox="0 0 19 1" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <line y1="0.5" x2="19" y2="0.5" stroke="currentColor"/>
                </svg>
            </span>

            <input type="text"
                   id="category-filter-steps-input-from"
                   data-start-min="0">
        </div>

        <div class="category-filter__price"
             id="category-filter-steps-slider"
             data-currency-sign="{{ config('app.currency.current_currency_symbol') }}">

        </div>
    </div>
</div>
