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

        <div class="grid grid-cols-[minmax(10%,0.5fr)_auto_minmax(10%,0.5fr)] items-center justify-between gap-x-5 mb-3">
            <input class="text-light-gray! border border-opacity-light-gray-40% px-4 py-2"
                   type="text"
                   id="category-filter-steps-input-to"
                   data-start-max="3000">

            <span>
                <svg width="19" height="1" viewBox="0 0 19 1" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <line y1="0.5" x2="19" y2="0.5" stroke="currentColor"/>
                </svg>
            </span>

            <input class="text-light-gray! border border-opacity-light-gray-40% px-4 py-2"
                   type="text"
                   id="category-filter-steps-input-from"
                   data-start-min="0">
        </div>

        <div class="category-filter__price w-full max-w-[97%] mx-auto mb-4"
             id="category-filter-steps-slider"
             data-currency-sign="{{ config('app.currency.current_currency_symbol') }}">
        </div>

        <div class="category-filter__items-list">
            <div class="accordion divide-opacity-light-gray-40% divide-y">
                @foreach($filters_data as $filter_data)
                    <div class="accordion-item {{ $loop->first ? 'active' : '' }}"
                         id="{{ $filter_data['group_id'] }}">
                        <button class="accordion-toggle inline-flex items-center justify-between gap-x-4 text-start {{ $loop->first ? 'border-t border-t-opacity-light-gray-40%' : '' }} p-2"
                                aria-controls="{{ $filter_data['group_id'] }}-collapse"
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                            <span>
                                {{ $filter_data['group_name'] }}
                            </span>

                            <span class="icon-[solar--alt-arrow-right-linear] accordion-item-active:-rotate-90 custom-icon shrink-0 transition-transform duration-300"></span>
                        </button>

                        <div id="{{ $filter_data['group_id'] }}-collapse" class="accordion-content {{ $loop->first ? '' : 'hidden' }} w-full overflow-hidden transition-[height] duration-300"
                             aria-labelledby="{{ $filter_data['group_id'] }}"
                             role="region">
                            <div class="flex flex-col gap-2 ps-2 pb-3">
                                @foreach($filter_data['items'] as $filter_item_data)
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox"
                                               class="checkbox "
                                               id="category-filter-item-{{ $filter_item_data['id'] }}"
                                               @if($filter_item_data['is_checked'] === true) checked @endif
                                               @if ($filter_item_data['total_products'] <= 0) disabled @endif />

                                        <label class="label-text"
                                               for="{{ $filter_item_data['id'] }}">
                                            {{ $filter_item_data['name'] }}
                                        </label>

                                        <span class="badge badge-outline {{ $filter_item_data['total_products'] > 0 ? '' : 'badge-error' }} ms-auto">
                                            {{ $filter_item_data['total_products'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
