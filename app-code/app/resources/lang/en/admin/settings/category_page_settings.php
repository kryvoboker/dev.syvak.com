<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Categories',

    'tabs' => [
        'sorting'           => 'Products sorting',
        'filters'           => 'Products filters',
        'localized_content' => 'Localized content',
    ],

    'labels' => [
        'model'                     => 'Category page settings',
        'plural_model'              => 'Category page settings',
        'is_sorting_enabled'        => 'Enable product sorting',
        'is_filtering_enabled'      => 'Enable product filters',
        'sorting_items'             => 'Sorting options',
        'filter_items'              => 'Filter options',
        'code'                      => 'Code',
        'source_type'               => 'Source type',
        'source_id'                 => 'Source ID',
        'is_enabled'                => 'Enabled',
        'sort_order'                => 'Sort order',
        'get_key'                   => 'GET key',
        'get_value'                 => 'GET value',
        'get_extra'                 => 'Extra GET params',
        'config'                    => 'Configuration',
        'selection_mode'            => 'Selection mode',
        'filter_mode'               => 'Filter mode',
        'filter_mode_helper'        => 'Choose how this filter behaves in the storefront.',
        'filter_mode_hint'          => 'Mode affects URL params and UI control type.',
        'filter_mode_description'   => 'Mode description',
        'min_price'                 => 'Min price',
        'max_price'                 => 'Max price',
        'step'                      => 'Step',
        'key'                       => 'Key',
        'value'                     => 'Value',
        'sorting_title'             => 'Sorting block title',
        'sorting_description'       => 'Sorting block description',
        'filters_title'             => 'Filters block title',
        'filters_description'       => 'Filters block description',
        'filters_drawer_title'      => 'Filter drawer title',
        'filters_apply_button_text' => 'Apply button text',
        'filters_clear_button_text' => 'Clear button text',
        'option_labels'             => 'Option labels',
        'option_label_value'        => 'Option label',
    ],

    'actions' => [
        'sync_filters' => 'Sync filters from catalog',
        'open_wiki'    => 'Open settings wiki',
    ],

    'notifications' => [
        'filters_synced' => 'Synchronization completed. Created: :created, updated: :updated, removed: :removed.',
    ],

    'filter_mode_options' => [
        'range'    => 'Range (from - to)',
        'boolean'  => 'Boolean (yes / no)',
        'multiple' => 'Multiple choice',
    ],

    'filter_mode_descriptions' => [
        'range'    => 'Use this for numeric ranges, e.g. price from-to sliders or inputs.',
        'boolean'  => 'Use this for binary filters, e.g. in stock / not in stock toggles.',
        'multiple' => 'Use this for selecting multiple values, e.g. product attributes.',
    ],

    'wiki' => [
        'title'                        => 'Category page settings wiki',
        'navigation_label'             => 'Wiki',
        'intro_title'                  => 'How to work with category page settings',
        'intro_description'            => 'This guide explains each settings block, what each field controls, and how to map it to practical storefront behavior.',
        'examples_title'               => 'Practical examples',
        'screenshot_missing_title'     => 'Screenshot is missing',
        'screenshot_missing_description' => 'Put the screenshot file at the path below to display it in this wiki section.',
        'table'                        => [
            'field'      => 'Field',
            'purpose'    => 'Purpose',
            'how_to_use' => 'How to use',
            'example'    => 'Example',
        ],
        'examples' => [
            'price_range'    => 'Price range filter: configure `get.extra.from_key` = `price_from` and `get.extra.to_key` = `price_to` to support URLs like `?price_from=100&price_to=500`.',
            'stock_toggle'   => 'Stock filter: use boolean mode with `get.key = stock` and `get.value = available`.',
            'attribute_multi' => 'Attribute filter: keep multiple mode with key like `attributes[5]` to pass multiple selected values.',
        ],
        'sections' => [
            'sorting' => [
                'title'                  => 'Sorting tab',
                'description'            => 'Controls which sorting variants are available and how they are encoded in query parameters.',
                'screenshot_description' => 'Reference screenshot of the Sorting tab in admin.',
            ],
            'filters' => [
                'title'                  => 'Filters tab',
                'description'            => 'Controls filter contracts (`get`, `get.extra`, `config`) that storefront code reads for query-driven filtering.',
                'screenshot_description' => 'Reference screenshot of the Filters tab in admin.',
            ],
            'localized' => [
                'title'                  => 'Localized content tab',
                'description'            => 'Controls UI texts for sorting/filter blocks and drawer controls per active language.',
                'screenshot_description' => 'Reference screenshot of the Localized content tab in admin.',
            ],
        ],
        'fields' => [
            'is_sorting_enabled' => [
                'purpose' => 'Global switch for all sorting controls on the category page.',
                'how'     => 'Disable when sorting UI should be hidden regardless of configured sorting items.',
                'example' => 'Turn off for landing categories where product order is fixed.',
            ],
            'sorting_items' => [
                'purpose' => 'List of sorting options visible for users.',
                'how'     => 'Manage enable state, order, and GET mapping for each sorting code.',
                'example' => 'Keep `default`, `newest`, and `price_asc` enabled; hide the rest.',
            ],
            'selection_mode' => [
                'purpose' => 'Describes how a sorting option should be selected in UI logic.',
                'how'     => 'Use `single` for standard one-choice sorting UX.',
                'example' => 'Set `single` for all sorting items.',
            ],
            'is_filtering_enabled' => [
                'purpose' => 'Global switch for all filters on the category page.',
                'how'     => 'Disable when filter drawer and related controls should be hidden.',
                'example' => 'Disable temporarily during catalog migration.',
            ],
            'get_contract' => [
                'purpose' => 'Defines the primary GET key/value pair for filter requests.',
                'how'     => 'Set keys used by frontend and backend query parsing.',
                'example' => '`get.key = stock`, `get.value = available`.',
            ],
            'get_extra' => [
                'purpose' => 'Stores additional query contract keys for advanced filters.',
                'how'     => 'Use extra keys for range boundaries or custom parsing hints.',
                'example' => 'Price filter: `from_key = price_from`, `to_key = price_to`.',
            ],
            'filter_mode' => [
                'purpose' => 'Defines expected behavior type for filter UI and parsing.',
                'how'     => 'Select only from whitelist modes configured in app config.',
                'example' => '`range` for price, `boolean` for stock, `multiple` for attributes.',
            ],
            'price_range' => [
                'purpose' => 'Defines numeric boundaries for price filter controls.',
                'how'     => 'Applied only for `price` filter code; hidden for other filters.',
                'example' => '`min_price = 100`, `max_price = 5000`, `step = 50`.',
            ],
            'sorting_content' => [
                'purpose' => 'Localized title/description for sorting block in storefront.',
                'how'     => 'Fill values per language tab for correct public UI texts.',
                'example' => 'EN: “Sorting”, UK: “Сортування”.',
            ],
            'filter_content' => [
                'purpose' => 'Localized title/description for filters block in storefront.',
                'how'     => 'Fill values per language tab to keep storefront localized.',
                'example' => 'EN: “Filter products”, UK: “Фільтр товарів”.',
            ],
            'drawer_content' => [
                'purpose' => 'Localized drawer labels and button captions for mobile filter UX.',
                'how'     => 'Set drawer title, apply and clear button text for each language.',
                'example' => 'EN: “Apply filters” / “Clear”, UK: “Застосувати” / “Очистити”.',
            ],
        ],
    ],
];
