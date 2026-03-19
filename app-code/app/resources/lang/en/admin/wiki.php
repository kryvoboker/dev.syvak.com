<?php

declare(strict_types=1);

return [
    'actions' => [
        'open_wiki' => 'Open wiki',
    ],

    'common' => [
        'examples_title'                 => 'Practical examples',
        'screenshot_missing_title'       => 'Screenshot not found',
        'screenshot_missing_description' => 'Put the screenshot file at the path below to display it in this wiki section.',
    ],

    'navigation' => [
        'page_settings_category' => 'Page Settings: Categories',
    ],

    'pages' => [
        'catalog_products' => [
            'title'             => 'Wiki: Catalog / Products',
            'navigation_label'  => 'Catalog: Products',
            'description'       => 'Guide for product catalog management in admin panel.',
            'intro_title'       => 'How to manage products',
            'intro_description' => 'This page explains product data structure, media, stock, prices, and multilingual content handling.',
            'examples'          => [
                'Use category assignment and slugs to keep storefront URLs stable.',
                'Use product attributes only after checking active language context.',
            ],
            'sections' => [
                [
                    'title'                  => 'Product base fields',
                    'description'            => 'Core fields required for product visibility and business logic.',
                    'screenshot'             => 'product-base-fields.png',
                    'screenshot_description' => 'Main data block in product form.',
                    'items'                  => [
                        'Model, SKU, EAN, quantity, minimum quantity.',
                        'Base price, active flag, availability date.',
                        'Primary image and additional gallery images.',
                    ],
                ],
                [
                    'title'                  => 'Translations and SEO',
                    'description'            => 'Localized content and SEO URLs per language.',
                    'screenshot'             => 'product-translations.png',
                    'screenshot_description' => 'Translations tabs and slug configuration.',
                    'items'                  => [
                        'Name, description, and optional SEO text fields per language.',
                        'Dedicated slug/URL per language to avoid SEO collisions.',
                    ],
                ],
            ],
        ],

        'catalog_categories' => [
            'title'             => 'Wiki: Catalog / Categories',
            'navigation_label'  => 'Catalog: Categories',
            'description'       => 'Guide for category tree and localized category metadata.',
            'intro_title'       => 'How to manage categories',
            'intro_description' => 'Categories control storefront navigation and product grouping. Keep tree and slugs consistent.',
            'examples'          => [
                'Create parent categories first, then attach children.',
                'Keep slugs short and language-specific for SEO readability.',
            ],
            'sections' => [
                [
                    'title'                  => 'Category structure',
                    'description'            => 'Build a consistent hierarchy using parent category field.',
                    'screenshot'             => 'category-tree.png',
                    'screenshot_description' => 'Category hierarchy controls.',
                    'items'                  => [
                        'Use parent category for nested trees.',
                        'Use sort order to control menu sequence.',
                    ],
                ],
                [
                    'title'                  => 'Localized content',
                    'description'            => 'Localized titles, descriptions, and slugs for each active language.',
                    'screenshot'             => 'category-translations.png',
                    'screenshot_description' => 'Language tabs with localized fields.',
                    'items'                  => [
                        'Name and description are set in each language tab.',
                        'Slug is configured per language for storefront URL.',
                    ],
                ],
            ],
        ],

        'catalog_attributes' => [
            'title'             => 'Wiki: Catalog / Attributes',
            'navigation_label'  => 'Catalog: Attributes',
            'description'       => 'Guide for product attributes and multilingual values.',
            'intro_title'       => 'How to manage attributes',
            'intro_description' => 'Attributes enrich products for filtering and detailed presentation.',
            'examples'          => [
                'Create reusable attributes before assigning them to products.',
                'Fill localized labels to avoid mixed-language storefront output.',
            ],
            'sections' => [
                [
                    'title'                  => 'Attribute lifecycle',
                    'description'            => 'Create, edit, and control active status for attribute records.',
                    'screenshot'             => 'attribute-list.png',
                    'screenshot_description' => 'Attributes list and actions.',
                    'items'                  => [
                        'Attribute title and active flag control availability.',
                        'Sort order defines UI sequence where used.',
                    ],
                ],
                [
                    'title'                  => 'Translations for attributes',
                    'description'            => 'Localized labels are used in product forms and storefront filters.',
                    'screenshot'             => 'attribute-translations.png',
                    'screenshot_description' => 'Translations tab for attributes.',
                    'items'                  => [
                        'Fill every active language tab for consistent UX.',
                    ],
                ],
            ],
        ],

        'info_pages' => [
            'title'             => 'Wiki: Info Pages',
            'navigation_label'  => 'Info Pages',
            'description'       => 'Guide for static informational pages and SEO metadata.',
            'intro_title'       => 'How to manage info pages',
            'intro_description' => 'Info pages are content-driven routes with localized body and metadata.',
            'examples'          => [
                'Use slugs per language to keep URLs human-readable.',
                'Use noindex for utility pages that should not be indexed.',
            ],
            'sections' => [
                [
                    'title'                  => 'Content blocks',
                    'description'            => 'Configure page title, body, and activity status.',
                    'screenshot'             => 'info-page-content.png',
                    'screenshot_description' => 'Main info page content form.',
                    'items'                  => [
                        'Localized title and content fields.',
                        'Page status and sorting controls.',
                    ],
                ],
                [
                    'title'                  => 'SEO setup',
                    'description'            => 'Meta fields and slugs for search engine behavior.',
                    'screenshot'             => 'info-page-seo.png',
                    'screenshot_description' => 'Meta fields and slug block.',
                    'items'                  => [
                        'Meta title/description/keywords per language.',
                        'Slug uniqueness per language.',
                    ],
                ],
            ],
        ],

        'users' => [
            'title'             => 'Wiki: Users',
            'navigation_label'  => 'Users: Users',
            'description'       => 'Guide for admin users and profile data management.',
            'intro_title'       => 'How to manage users',
            'intro_description' => 'Use this page to maintain admin accounts, contacts, and access context.',
            'examples'          => [
                'Create users with verified email only when onboarding is complete.',
                'Update contacts to keep notification and support flows accurate.',
            ],
            'sections' => [
                [
                    'title'                  => 'Account fields',
                    'description'            => 'Core profile and authentication data.',
                    'screenshot'             => 'users-main.png',
                    'screenshot_description' => 'User profile form.',
                    'items'                  => [
                        'Name, lastname, email, telephone, avatar.',
                        'Password and confirmation when creating/updating credentials.',
                    ],
                ],
            ],
        ],

        'user_groups' => [
            'title'             => 'Wiki: Users / User Groups',
            'navigation_label'  => 'Users: User Groups',
            'description'       => 'Guide for grouping users by permission scope and business meaning.',
            'intro_title'       => 'How to manage user groups',
            'intro_description' => 'User groups are used for segmentation and permission-related flows.',
            'examples'          => [
                'Create a separate group for internal managers and content editors.',
            ],
            'sections' => [
                [
                    'title'                  => 'Group metadata',
                    'description'            => 'Set group name and descriptive context for admins.',
                    'screenshot'             => 'user-groups-main.png',
                    'screenshot_description' => 'User group form.',
                    'items'                  => [
                        'Use clear names to reflect role boundaries.',
                        'Keep naming consistent with ACL policies.',
                    ],
                ],
            ],
        ],

        'modules' => [
            'title'             => 'Wiki: Modules',
            'navigation_label'  => 'Modules',
            'description'       => 'Guide for module lifecycle, instance settings, and runtime state.',
            'intro_title'       => 'How to manage modules',
            'intro_description' => 'Modules page controls activation, synchronization, and instance-level settings.',
            'examples'          => [
                'Synchronize module definitions after filesystem/module package changes.',
                'Use instance placement and page type settings to control storefront output.',
            ],
            'sections' => [
                [
                    'title'                  => 'Definition and instance flow',
                    'description'            => 'Understand global module state vs instance state.',
                    'screenshot'             => 'modules-list.png',
                    'screenshot_description' => 'Definitions list with linked instances.',
                    'items'                  => [
                        'Definition enable/disable affects all related instances.',
                        'Instance settings are edited separately per module placement/context.',
                    ],
                ],
            ],
        ],

        'application_currencies' => [
            'title'             => 'Wiki: Application Settings / Currencies',
            'navigation_label'  => 'Application Settings: Currencies',
            'description'       => 'Guide for currency configuration and exchange rate updates.',
            'intro_title'       => 'How to manage currencies',
            'intro_description' => 'Currencies control price formatting and conversion behavior across storefront.',
            'examples'          => [
                'Keep exactly one default active currency for stable conversions.',
                'Use rate update action to sync values before promotions.',
            ],
            'sections' => [
                [
                    'title'                  => 'Currency fields',
                    'description'            => 'Code, symbols, format locale, and default flags.',
                    'screenshot'             => 'currencies-main.png',
                    'screenshot_description' => 'Currency list and form essentials.',
                    'items'                  => [
                        'Code and locale affect display formatting.',
                        'Default currency is used as conversion anchor.',
                    ],
                ],
            ],
        ],

        'application_languages' => [
            'title'             => 'Wiki: Application Settings / Languages',
            'navigation_label'  => 'Application Settings: Languages',
            'description'       => 'Guide for active locales and localization runtime behavior.',
            'intro_title'       => 'How to manage languages',
            'intro_description' => 'Languages define available translation tabs and storefront locale options.',
            'examples'          => [
                'Activate language only after core translation keys are prepared.',
                'Keep one language as default to stabilize fallback behavior.',
            ],
            'sections' => [
                [
                    'title'                  => 'Language fields',
                    'description'            => 'Name, code, locale format, active/default flags.',
                    'screenshot'             => 'languages-main.png',
                    'screenshot_description' => 'Languages list and edit form.',
                    'items'                  => [
                        'Language code must match localization conventions used in project.',
                        'Default language impacts admin/storefront initial locale.',
                    ],
                ],
            ],
        ],

        'application_settings' => [
            'title'             => 'Wiki: Application Settings / Global',
            'navigation_label'  => 'Application Settings: Global',
            'description'       => 'Guide for global site configuration values used by storefront and integrations.',
            'intro_title'       => 'How to manage global app settings',
            'intro_description' => 'This page controls shared texts, contacts, social links, and technical display settings.',
            'examples'          => [
                'Keep contact blocks localized for each active language.',
                'Validate social links and map embeds after each update.',
            ],
            'sections' => [
                [
                    'title'                  => 'Localized blocks',
                    'description'            => 'Titles, contact data, addresses, and socials by language.',
                    'screenshot'             => 'app-settings-localized.png',
                    'screenshot_description' => 'Localized tabs in global settings form.',
                    'items'                  => [
                        'Fill phone/email/socials in each active language where needed.',
                        'Use valid SVG for custom social icons.',
                    ],
                ],
                [
                    'title'                  => 'Global technical options',
                    'description'            => 'Timezone, map settings, and image size presets.',
                    'screenshot'             => 'app-settings-technical.png',
                    'screenshot_description' => 'Non-localized shared settings.',
                    'items'                  => [
                        'Timezone affects date displays and scheduled tasks.',
                        'Image size presets are reused in image processing flows.',
                    ],
                ],
            ],
        ],
    ],
];
