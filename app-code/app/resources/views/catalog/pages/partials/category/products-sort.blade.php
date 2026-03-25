<div class="dropdown category-sort-dropdown relative">
    <button class="dropdown-toggle filter-sort-btns"
            type="button"
            aria-haspopup="menu"
            aria-expanded="false"
            aria-label="Sort products dropdown">
        <span>
            {{ __('catalog/default.buttons.sort') }}
        </span>

        <span class="icon-[fluent--filter-24-regular] custom-icon dropdown-open:rotate-180 duration-100 ease-in-out"></span>
    </button>

    <ul class="dropdown-menu dropdown-open:opacity-100 hidden min-w-52 border border-opacity-light-gray-40% bg-black p-3 md:p-4"
        role="menu"
        aria-orientation="vertical">
        <button class="close-category-sort-dropdown-btn mb-1 ms-auto block"
                type="button"
                aria-label="Close sort dropdown">
            <span class="icon-[ic--baseline-close] custom-icon"></span>
        </button>

        @foreach($category_page_settings['sort_options'] as $sort_option)
            @php
                $sort_code = (string) data_get($sort_option, 'code', '');
                $sort_value = (string) data_get($sort_option, 'value', '');
                $sort_label = (string) data_get($sort_option, 'label', '');
                $is_current_sort = $sort_code === (string) $active_sort_code;
            @endphp

            <li class="border-b border-opacity-light-gray-40% last:border-b-0">
                <button class="w-full p-2 text-left text-11px uppercase tracking-0.04em duration-200 ease-in-out hover:text-white md:text-sm lg:text-base
                                              {{ $is_current_sort ? 'text-white' : 'text-light-gray' }}"
                        type="button"
                        data-sort-code="{{ $sort_code }}"
                        data-sort-value="{{ $sort_value }}"
                        aria-current="{{ $is_current_sort ? 'true' : 'false' }}">
                    {{ $sort_label }}
                </button>
            </li>
        @endforeach
    </ul>
</div>
