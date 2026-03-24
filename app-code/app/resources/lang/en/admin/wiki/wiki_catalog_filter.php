<?php

declare(strict_types=1);

return [
    'title'             => 'Wiki: Catalog / Catalog Filter',
    'navigation_label'  => 'Catalog: Catalog Filter',
    'description'       => 'Guide for catalog filtering subsystem configuration and operational actions.',
    'intro_title'       => 'How catalog filter works',
    'intro_description' => 'Catalog Filter is a standalone subsystem. It stores filter contracts, strategies, and index runtime state independently from Page Settings.',
    'examples'          => [
        'Use "Sync Groups" after attribute dictionary updates.',
        'Use "Sync Values" after product attribute values change.',
        'Use "Refresh Index Status" to reload runtime meta without running sync actions.',
    ],
    'sections' => [
        [
            'title'       => 'General and strategy settings',
            'description' => 'Core flags and strategy selectors that control filter behavior in runtime.',
            'fields'      => [
                [
                    'label'   => 'Context Type',
                    'purpose' => 'Defines where this filter set is applied (category/catalog/search).',
                    'how'     => 'Keep one deterministic context for the main storefront usage scenario.',
                    'example' => 'category',
                ],
                [
                    'label'   => 'Price Source Mode / Discount Policy',
                    'purpose' => 'Controls which prices are used for indexing and filtering.',
                    'how'     => 'Select one mode and keep policy explicit for discount-only behavior.',
                    'example' => 'Mode: both, Policy: exclude_without_discount',
                ],
                [
                    'label'   => 'Facet Strategy',
                    'purpose' => 'Controls how facet counts are calculated.',
                    'how'     => 'Use a strategy that matches UX expectations for filter counters.',
                    'example' => 'self_excluding',
                ],
                [
                    'label'   => 'Minimum stock quantity',
                    'purpose' => 'Limits indexed/visible products to items with enough stock.',
                    'how'     => 'Set N and only products with quantity >= N participate in filtering.',
                    'example' => '5',
                ],
            ],
        ],
        [
            'title'       => 'Rebuild and safety controls',
            'description' => 'Parameters for rebuild safety and runtime constraints.',
            'fields'      => [
                [
                    'label'   => 'Rebuild Chunk Size / Lock Timeout',
                    'purpose' => 'Controls rebuild workload slicing and lock duration.',
                    'how'     => 'Tune values according to product volume and server capacity.',
                    'example' => 'Chunk=1000, Lock timeout=600 seconds',
                ],
                [
                    'label'   => 'Max Selected Values Per Group',
                    'purpose' => 'Guards runtime from oversized filter payloads.',
                    'how'     => 'Keep hard cap realistic for UI and query complexity.',
                    'example' => '30',
                ],
                [
                    'label'   => 'Base Currency Indexing Required',
                    'purpose' => 'Keeps index values stable and avoids currency drift.',
                    'how'     => 'Keep enabled to index in base currency and convert only on output.',
                    'example' => 'Enabled',
                ],
            ],
        ],
        [
            'title'       => 'Filter options tab',
            'description' => 'Point-by-point editor for each filter group. The tab is stored in Catalog Filter tables and does not depend on Page Settings.',
            'fields'      => [
                [
                    'label'   => 'Filter options rows',
                    'purpose' => 'Each row configures one group like price or one attribute.',
                    'how'     => 'Change is_enabled, sort_order, GET key/value/extra, and mode for each row.',
                    'example' => 'Set price row mode to range with custom GET extras.',
                ],
                [
                    'label'   => 'GET key',
                    'purpose' => 'Parameter name that will be used in URL.',
                    'how'     => 'Edit only if you understand your URL contract; otherwise keep the default.',
                    'example' => 'filters[21] or price',
                ],
                [
                    'label'   => 'GET value',
                    'purpose' => 'Parameter value for a filter row in URL.',
                    'how'     => 'Can stay empty for rows where values are formed from selected options automatically.',
                    'example' => 'in_stock',
                ],
                [
                    'label'   => 'GET extra',
                    'purpose' => 'Additional URL keys for advanced filter contracts.',
                    'how'     => 'Use for range cases, e.g. from/to keys.',
                    'example' => 'from_key=price_from, to_key=price_to',
                ],
                [
                    'label'   => 'Localized option labels',
                    'purpose' => 'Stores labels for each active language directly in Catalog Filter translations.',
                    'how'     => 'Fill labels in each language tab for stable multilingual storefront output.',
                    'example' => 'en: Age, uk: Вік',
                ],
                [
                    'label'   => 'Sync compatibility policy',
                    'purpose' => 'Explains what sync can regenerate and what remains user-managed.',
                    'how'     => 'Sync keeps is_enabled, sort_order, get_key, and config; source metadata and labels are regenerated from source data.',
                    'example' => 'After Sync Groups, manual mode and GET mapping are preserved.',
                ],
            ],
        ],
        [
            'title'       => 'Operational actions and index meta',
            'description' => 'Actions for synchronization and read-only runtime status block.',
            'fields'      => [
                [
                    'label'   => 'Sync Groups',
                    'purpose' => 'Regenerates filter groups from active attributes and system groups.',
                    'how'     => 'Run after changes in attribute dictionary or activation flags.',
                    'example' => 'After creating new active attribute "Age"',
                ],
                [
                    'label'   => 'Sync Values',
                    'purpose' => 'Regenerates value options per group from active product data.',
                    'how'     => 'Run after product attribute value changes.',
                    'example' => 'After importing products with new attribute values',
                ],
                [
                    'label'   => 'Refresh Index Status',
                    'purpose' => 'Refreshes read-only runtime metadata without sync actions.',
                    'how'     => 'Use for quick monitoring of active/building versions and lock state.',
                    'example' => 'Check active_index_version and last_status',
                ],
            ],
        ],
    ],
];
