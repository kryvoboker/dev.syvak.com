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
