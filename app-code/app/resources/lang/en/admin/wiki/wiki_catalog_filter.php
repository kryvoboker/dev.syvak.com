<?php

declare(strict_types=1);

return [
    'title' => 'Wiki: Catalog / Catalog Filter',
    'navigation_label' => 'Catalog: Catalog Filter',
    'description' => 'Guide for catalog filtering subsystem configuration and operational actions.',
    'intro_title' => 'How catalog filter works',
    'intro_description' => 'Catalog Filter is a standalone subsystem. It stores filter contracts, strategies, and index runtime state independently from Page Settings.',
    'examples' => [
        'Use "Sync Groups" after attribute dictionary updates.',
        'Use "Sync Values" after product attribute values change.',
        'Use "Refresh Index Status" to reload runtime meta without running sync actions.',
    ],
    'sections' => [
        [
            'title' => 'General and strategy settings',
            'description' => 'Core flags and strategy selectors that control filter behavior in runtime.',
            'fields' => [
                [
                    'label' => 'Context Type',
                    'purpose' => 'Defines where this filter set is applied (category/catalog/search).',
                    'how' => 'Keep one deterministic context for the main storefront usage scenario.',
                    'example' => 'category',
                ],
                [
                    'label' => 'Price Source Mode / Discount Policy',
                    'purpose' => 'Controls which prices are used for indexing and filtering.',
                    'how' => 'Select one mode and keep policy explicit for discount-only behavior.',
                    'example' => 'Mode: both, Policy: exclude_without_discount',
                ],
                [
                    'label' => 'Facet Strategy',
                    'purpose' => 'Controls how facet counts are calculated.',
                    'how' => 'Use a strategy that matches UX expectations for filter counters.',
                    'example' => 'self_excluding',
                ],
                [
                    'label' => 'Minimum stock quantity',
                    'purpose' => 'Limits indexed/visible products to items with enough stock.',
                    'how' => 'Set N and only products with quantity >= N participate in filtering.',
                    'example' => '5',
                ],
            ],
        ],
        [
            'title' => 'Rebuild and safety controls',
            'description' => 'Parameters for rebuild safety and runtime constraints.',
            'fields' => [
                [
                    'label' => 'Rebuild Chunk Size / Lock Timeout',
                    'purpose' => 'Controls rebuild workload slicing and lock duration.',
                    'how' => 'Tune values according to product volume and server capacity.',
                    'example' => 'Chunk=1000, Lock timeout=600 seconds',
                ],
                [
                    'label' => 'Max Selected Values Per Group',
                    'purpose' => 'Guards runtime from oversized filter payloads.',
                    'how' => 'Keep hard cap realistic for UI and query complexity.',
                    'example' => '30',
                ],
                [
                    'label' => 'Base Currency Indexing Required',
                    'purpose' => 'Keeps index values stable and avoids currency drift.',
                    'how' => 'Keep enabled to index in base currency and convert only on output.',
                    'example' => 'Enabled',
                ],
            ],
        ],
        [
            'title' => 'Filter options tab',
            'description' => 'Point-by-point editor for each filter group. The tab is stored in Catalog Filter tables and does not depend on Page Settings.',
            'fields' => [
                [
                    'label' => 'Filter options rows',
                    'purpose' => 'Each row configures one group like price or one attribute.',
                    'how' => 'Change is_enabled, sort_order, GET key/value/extra, and mode for each row.',
                    'example' => 'Set price row mode to range with custom GET extras.',
                ],
                [
                    'label' => 'GET key',
                    'purpose' => 'Parameter name in URL for this filter group. Backend first finds this key, then parses its value.',
                    'how' => 'Treat it as the input field name in query string. If key is `weight`, backend reads `?weight=...`; if key is `filters[21]`, backend reads `?filters[21]=...`.',
                    'example' => 'URL: ?weight=light,medium -> values `light`, `medium` are matched against `catalog_filter_values.code`.',
                ],
                [
                    'label' => 'GET value',
                    'purpose' => 'Optional fallback value for legacy/flag-style URLs. Not required for normal filtering flow.',
                    'how' => 'Use only when URL sends a marker (`1`, `true`, `on`, `yes`) instead of a real option code. If your frontend always sends real codes, keep this field empty.',
                    'example' => 'Configured: get_key=attribute, get_value=light. URL: ?attribute=1 -> backend normalizes to value `light`.',
                ],
                [
                    'label' => 'GET extra',
                    'purpose' => 'Additional URL key mapping for complex filters (main case: ranges).',
                    'how' => 'Use when one group needs multiple query keys. Most common example is price range with separate `from` and `to` keys.',
                    'example' => 'get.extra.from_key=price_from, get.extra.to_key=price_to -> URL: ?price_from=100&price_to=500',
                ],
                [
                    'label' => 'Practical example: GET key',
                    'purpose' => 'Recommended daily scenario.',
                    'how' => 'Frontend sends real filter codes, backend reads them by `get_key` and applies filtering.',
                    'example' => 'Set `get_key=weight`. URL: ?weight=light,medium -> backend uses codes `light`, `medium`.',
                ],
                [
                    'label' => 'Practical example: GET value',
                    'purpose' => 'Fallback scenario for old links or external systems.',
                    'how' => 'When URL provides a flag marker and not a real code, backend substitutes configured `get_value`.',
                    'example' => 'Set `get_key=attribute`, `get_value=light`. URL: ?attribute=1 -> attributes[attribute_id]=["light"]',
                ],
                [
                    'label' => 'Practical example: GET extra',
                    'purpose' => 'Price range contract.',
                    'how' => 'Backend reads two separate keys and fills normalized `price_from`/`price_to` fields.',
                    'example' => 'Set `get.extra.from_key=price_from`, `get.extra.to_key=price_to`. URL: ?price_from=100&price_to=500',
                ],
                [
                    'label' => 'URL -> normalized payload mapping',
                    'purpose' => 'Shows what backend actually uses for query building after URL parsing.',
                    'how' => 'Step 1: read GET keys. Step 2: normalize values. Step 3: build internal payload for filtering.',
                    'example' => '?weight=light,medium&price_from=100&price_to=500 -> {attributes:{21:["light","medium"]}, price_from:100, price_to:500}',
                ],
                [
                    'label' => 'Localized option labels',
                    'purpose' => 'Stores labels for each active language directly in Catalog Filter translations.',
                    'how' => 'Fill labels for all active languages. The number of locales can change over time, so avoid contracts that assume exactly two locales.',
                    'example' => 'locale A: Age, locale B: Вік, locale C: Alter',
                ],
                [
                    'label' => 'Sync compatibility policy',
                    'purpose' => 'Explains what sync can regenerate and what remains user-managed.',
                    'how' => 'Sync keeps is_enabled, sort_order, get_key, and config; source metadata and labels are regenerated from source data.',
                    'example' => 'After Sync Groups, manual mode and GET mapping are preserved.',
                ],
            ],
        ],
        [
            'title' => 'Operational actions and index meta',
            'description' => 'Actions for synchronization and read-only runtime status block.',
            'fields' => [
                [
                    'label' => 'Sync Groups',
                    'purpose' => 'Regenerates filter groups from active attributes and system groups.',
                    'how' => 'Run after changes in attribute dictionary or activation flags.',
                    'example' => 'After creating new active attribute "Age"',
                ],
                [
                    'label' => 'Sync Values',
                    'purpose' => 'Regenerates value options per group from active product data.',
                    'how' => 'Run after product attribute value changes.',
                    'example' => 'After importing products with new attribute values',
                ],
                [
                    'label' => 'Refresh Index Status',
                    'purpose' => 'Refreshes read-only runtime metadata without sync actions.',
                    'how' => 'Use for quick monitoring of active/building versions and lock state.',
                    'example' => 'Check active_index_version and last_status',
                ],
            ],
        ],
    ],
];
