@php
    $filters = [
        'group-id-1' => [
            'group_id' => 'group-id-1',
            'group_name' => 'filter-group-1',
            'items' => [
                'item-id-1' => [
                    'id' => 1,
                    'name' => 'filter-item-1',
                    'total_products' => 100,
                    'is_checked' => true,
                ],
                'item-id-2' => [
                    'id' => 2,
                    'name' => 'filter-item-2',
                    'total_products' => 0,
                    'is_checked' => false
                ],
                'item-id-3' => [
                    'id' => 3,
                    'name' => 'filter-item-3',
                    'total_products' => 14,
                    'is_checked' => false,
                ],
                'item-id-4' => [
                    'id' => 4,
                    'name' => 'filter-item-4',
                    'total_products' => 13,
                    'is_checked' => true,
                ],
                'item-id-5' => [
                    'id' => 5,
                    'name' => 'filter-item-5',
                    'total_products' => 25,
                    'is_checked' => true,
                ],
            ],
        ],
        'group-id-2' => [
            'group_id' => 'group-id-2',
            'group_name' => 'filter-group-2',
            'items' => [
                'item-id-6' => [
                    'id' => 6,
                    'name' => 'filter-item-6',
                    'total_products' => 25,
                    'is_checked' => false,
                ],
                'item-id-7' => [
                    'id' => 7,
                    'name' => 'filter-item-7',
                    'total_products' => 25,
                    'is_checked' => true,
                ],
                'item-id-8' => [
                    'id' => 8,
                    'name' => 'filter-item-8',
                    'total_products' => 215,
                    'is_checked' => false,
                ],
                'item-id-9' => [
                    'id' => 9,
                    'name' => 'filter-item-9',
                    'total_products' => 25,
                    'is_checked' => false,
                ],
                'item-id-10' => [
                    'id' => 10,
                    'name' => 'filter-item-10',
                    'total_products' => 250,
                    'is_checked' => false,
                ],
            ],
        ],
        'group-id-3' => [
            'group_id' => 'group-id-3',
            'group_name' => 'filter-group-3',
            'items' => [
                'item-id-11' => [
                    'id' => 11,
                    'name' => 'filter-item-11',
                    'total_products' => 25,
                    'is_checked' => false,
                ],
                'item-id-12' => [
                    'id' => 12,
                    'name' => 'filter-item-12',
                    'total_products' => 25,
                    'is_checked' => false,
                ],
                'item-id-13' => [
                    'id' => 13,
                    'name' => 'filter-item-13',
                    'total_products' => 25,
                    'is_checked' => false,
                ],
                'item-id-14' => [
                    'id' => 14,
                    'name' => 'filter-item-14',
                    'total_products' => 25,
                    'is_checked' => false,
                ],
                'item-id-15' => [
                    'id' => 15,
                    'name' => 'filter-item-15',
                    'total_products' => 25,
                    'is_checked' => false,
                ],
            ],
        ],
        'group-id-4' => [
            'group_id' => 'group-id-4',
            'group_name' => 'filter-group-4',
            'total_products' => 25,
            'items' => [
                'item-id-16' => [
                    'id' => 16,
                    'name' => 'filter-item-16',
                    'total_products' => 25,
                    'is_checked' => false,
                ],
                'item-id-17' => [
                    'id' => 17,
                    'name' => 'filter-item-17',
                    'total_products' => 25,
                    'is_checked' => false,
                ],
                'item-id-18' => [
                    'id' => 18,
                    'name' => 'filter-item-18',
                    'total_products' => 25,
                    'is_checked' => false,
                ],
                'item-id-19' => [
                    'id' => 19,
                    'name' => 'filter-item-19',
                    'total_products' => 25,
                    'is_checked' => true,
                ],
                'item-id-20' => [
                    'id' => 20,
                    'name' => 'filter-item-20',
                    'total_products' => 25,
                    'is_checked' => true,
                ],
            ],
        ],
    ];
@endphp

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
                @foreach($filters as $filter)
                    <div class="accordion-item {{ $loop->first ? 'active' : '' }}" id="{{ $filter['group_id'] }}">
                        <button class="accordion-toggle inline-flex items-center justify-between gap-x-4 text-start {{ $loop->first ? 'border-t border-t-opacity-light-gray-40%' : '' }} p-2"
                                aria-controls="{{ $filter['group_id'] }}-collapse"
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                            <span>
                                {{ $filter['group_name'] }}
                            </span>

                            <span class="icon-[solar--alt-arrow-right-linear] accordion-item-active:-rotate-90 custom-icon shrink-0 transition-transform duration-300"></span>
                        </button>

                        <div id="{{ $filter['group_id'] }}-collapse" class="accordion-content {{ $loop->first ? '' : 'hidden' }} w-full overflow-hidden transition-[height] duration-300"
                             aria-labelledby="{{ $filter['group_id'] }}"
                             role="region">
                            <div class="flex flex-col gap-2 ps-2 pb-3">
                                @foreach($filter['items'] as $filter_item_data)
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox"
                                               class="checkbox "
                                               id="{{ $filter_item_data['id'] }}"
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
