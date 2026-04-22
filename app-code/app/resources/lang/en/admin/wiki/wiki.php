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
        'table'                          => [
            'field'      => 'Field',
            'purpose'    => 'Purpose',
            'how_to_use' => 'How to use',
            'example'    => 'Example',
        ],
    ],

    'pages' => [
        'page_settings_category' => [
            'title'             => 'Wiki: Page Settings / Categories',
            'navigation_label'  => 'Page Settings: Categories',
            'description'       => 'Full guide for category page sorting and filtering settings.',
            'intro_title'       => 'How category page settings work',
            'intro_description' => 'Use this page to control sorting/filter UI and GET contracts used by storefront product listing logic.',
            'examples'          => [
                'Enable only sorting options that your catalog logic supports.',
                'For price filter use explicit min/max/step values to avoid noisy ranges.',
                'Keep localized labels aligned with active languages to avoid mixed-language UI.',
            ],
            'sections' => [
                [
                    'title'                  => 'Sorting block',
                    'description'            => 'Controls sorting feature switch and available sorting options.',
                    'screenshot'             => 'sorting-tab.png',
                    'screenshot_description' => 'Sorting settings tab on category page settings form.',
                    'fields'                 => [
                        [
                            'label'   => 'Enable sorting',
                            'purpose' => 'Global switch for sorting UI on category page.',
                            'how'     => 'Turn on to render sorting dropdown on storefront.',
                            'example' => 'Enabled for product-heavy categories, disabled for landing-like categories.',
                        ],
                        [
                            'label'   => 'Sorting items',
                            'purpose' => 'Defines allowed sorting options and GET params.',
                            'how'     => 'Keep codes, GET keys, and values stable for backend processors.',
                            'example' => 'newest => ?sort=newest, cheap_first => ?sort=price_asc.',
                        ],
                    ],
                ],
                [
                    'title'                  => 'Filters block',
                    'description'            => 'Controls filter options, GET contract, and price mode settings.',
                    'screenshot'             => 'filters-tab.png',
                    'screenshot_description' => 'Filters settings tab with filter items contract.',
                    'fields'                 => [
                        [
                            'label'   => 'Enable filtering',
                            'purpose' => 'Global switch for filter UI and filter processing.',
                            'how'     => 'Turn off if category should show static products without filtering.',
                            'example' => 'Disabled for promo pages, enabled for full catalog pages.',
                        ],
                        [
                            'label'   => 'GET key / value / extra',
                            'purpose' => 'Defines URL contract consumed by storefront filtering logic.',
                            'how'     => 'Configure deterministic keys and values per filter item.',
                            'example' => 'light => key=attribute, value=1, extra=toggle.',
                        ],
                        [
                            'label'   => 'Filter mode + price range config',
                            'purpose' => 'Controls filter behavior by type (range, checkbox, toggle).',
                            'how'     => 'For price use min/max/step; for non-price keep only mode + GET contract.',
                            'example' => 'Price range: min=100, max=10000, step=100.',
                        ],
                    ],
                ],
                [
                    'title'                  => 'Localized content block',
                    'description'            => 'Controls labels and helper texts for sorting/filtering UI per language.',
                    'screenshot'             => 'localized-tab.png',
                    'screenshot_description' => 'Localized tab with sorting and filter texts for each active language.',
                    'fields'                 => [
                        [
                            'label'   => 'Sorting title and description',
                            'purpose' => 'UI texts for sorting block in storefront.',
                            'how'     => 'Fill per active language tab.',
                            'example' => 'EN: “Sort products”, UK: “Сортування товарів”.',
                        ],
                        [
                            'label'   => 'Filters title, drawer title and buttons',
                            'purpose' => 'UI texts for filter drawer and controls.',
                            'how'     => 'Keep wording short and action-oriented for mobile and desktop.',
                            'example' => 'Apply button: “Show products”, Clear button: “Reset filters”.',
                        ],
                    ],
                ],
            ],
        ],

        'catalog_products' => [
            'title'             => 'Wiki: Catalog / Products',
            'navigation_label'  => 'Catalog: Products',
            'description'       => 'Guide for product and product-variant management in admin panel.',
            'intro_title'       => 'How to manage products',
            'intro_description' => 'This module uses a parent product + variants model. Storefront renders a concrete variant, but can use parent product fields as fallback when variant-specific data is missing.',
            'examples'          => [
                'Fill parent product baseline data first, then create variants.',
                'Create variants only with unique attribute combinations per parent product.',
                'Keep product and variant slugs unique to avoid SEO conflicts.',
            ],
            'sections' => [
                [
                    'title'       => 'Core model: parent product + variants',
                    'description' => 'Parent product stores shared data. Variants represent concrete sellable combinations (for example, color + size).',
                    'fields'      => [
                        [
                            'label'   => 'Parent product',
                            'purpose' => 'Holds shared content and fallback values.',
                            'how'     => 'Fill baseline fields: title, description, SEO, media, price, stock.',
                            'example' => '“Syvak T-shirt” as the main product container.',
                        ],
                        [
                            'label'   => 'Product variants',
                            'purpose' => 'Represent specific product modifications available for sale.',
                            'how'     => 'Create one variant per real attribute combination.',
                            'example' => 'Color: White + Size: L/XL as a separate variant.',
                        ],
                    ],
                ],
                [
                    'title'       => 'What to fill in a variant',
                    'description' => 'Variant can have its own attributes, translations, slugs, stock, prices, images, and additional content.',
                    'fields'      => [
                        [
                            'label'   => 'Attributes',
                            'purpose' => 'Define a unique variant combination.',
                            'how'     => 'Select attribute values that actually exist in stock.',
                            'example' => 'Material: Cotton, Color: White, Size: L/XL.',
                        ],
                        [
                            'label'   => 'Translations + Slugs',
                            'purpose' => 'Localized storefront content and SEO URLs for the concrete variant.',
                            'how'     => 'Fill translations for active languages and set slug per language.',
                            'example' => 'UK slug: bereginya-sadu, EN slug: garden-guardian.',
                        ],
                        [
                            'label'   => 'Stock / Price / Images',
                            'purpose' => 'Controls availability and commercial data for this variant.',
                            'how'     => 'Set quantity, price, discount price, and variant media.',
                            'example' => 'Variant “black, M” has its own price and gallery.',
                        ],
                        [
                            'label'   => 'Is primary (for images)',
                            'purpose' => 'Marks the main variant image shown first on product card and listing previews.',
                            'how'     => 'Keep `is primary` enabled only for one image in the variant gallery.',
                            'example' => 'Primary image is used as the first card image and main preview.',
                        ],
                    ],
                ],
                [
                    'title'       => 'Validation and integrity rules',
                    'description' => 'Admin validation blocks conflicting data between sibling variants of the same parent product.',
                    'fields'      => [
                        [
                            'label'   => 'Slug uniqueness',
                            'purpose' => 'Prevents duplicate SEO URLs between products and variants.',
                            'how'     => 'Use different slug values for different entities.',
                            'example' => 'Two different variants cannot share the same slug.',
                        ],
                        [
                            'label'   => 'Attribute-combination uniqueness',
                            'purpose' => 'Prevents creating duplicate variants.',
                            'how'     => 'Do not create another variant with the same full attribute set.',
                            'example' => '“White + L/XL” is allowed only once per parent product.',
                        ],
                        [
                            'label'   => 'Single default variant',
                            'purpose' => 'Defines which variant is opened first on product page.',
                            'how'     => 'Keep only one default variant for each parent product.',
                            'example' => 'Default variant = most common in-stock option.',
                        ],
                    ],
                ],
                [
                    'title'       => 'Storefront resolution logic',
                    'description' => 'Product page resolves the active variant by variant slug or by attribute GET parameters.',
                    'fields'      => [
                        [
                            'label'   => 'Resolution priority',
                            'purpose' => 'Keeps variant resolution deterministic.',
                            'how'     => 'Variant slug has priority; if slug is missing, attributes from GET are used.',
                            'example' => '/product/{product-slug}/{variant-slug} or /product/{product-slug}?attribute=white,m.',
                        ],
                        [
                            'label'   => 'Variant switching',
                            'purpose' => 'Allows user to navigate between valid attribute combinations.',
                            'how'     => 'Selecting an attribute value builds URL for another valid variant.',
                            'example' => 'Clicking “Black” redirects to URL of “black + current size” variant.',
                        ],
                        [
                            'label'   => 'Fallback behavior',
                            'purpose' => 'Guarantees complete page output when variant data is partial.',
                            'how'     => 'If a variant field is empty, parent product value is used.',
                            'example' => 'Missing variant description => parent description is rendered.',
                        ],
                    ],
                ],
                [
                    'title'       => 'Common mistakes and how to avoid them',
                    'description' => 'A quick checklist for cases when variant switching fails or product page data looks inconsistent.',
                    'items'       => [
                        'Duplicate slug between product/variant: assign a unique slug value.',
                        'Same attribute combination across two variants: sibling variants must have unique combinations.',
                        'Multiple default variants for one parent product: keep only one default variant.',
                        'Variant has empty fields: storefront uses fallback values from the parent product.',
                        'No `is primary` image on variant gallery: main image can be ambiguous, set primary explicitly.',
                    ],
                ],
            ],
        ],

        'catalog_categories' => [
            'title'             => 'Wiki: Catalog / Categories',
            'navigation_label'  => 'Catalog: Categories',
            'description'       => 'Guide for category tree and localized category metadata.',
            'intro_title'       => 'How to manage categories',
            'intro_description' => 'Categories drive storefront menu structure and product grouping.',
            'examples'          => [
                'Create parent categories before child branches.',
                'Use sort order for deterministic menu order.',
            ],
            'sections' => [
                [
                    'title'                  => 'Category configuration',
                    'description'            => 'Main category form data and hierarchy setup.',
                    'screenshot'             => 'main.png',
                    'screenshot_description' => 'Categories list and create/edit entry points.',
                    'fields'                 => [
                        [
                            'label'   => 'Parent category',
                            'purpose' => 'Builds category hierarchy used in storefront navigation.',
                            'how'     => 'Leave empty for root category, select parent for nested levels.',
                            'example' => 'Root: “Flowers”; child: “Roses” with parent “Flowers”.',
                        ],
                        [
                            'label'   => 'Sort order + Active',
                            'purpose' => 'Controls visibility and sequence in menu blocks.',
                            'how'     => 'Assign lower order for top-priority categories.',
                            'example' => 'Sort order 1 for best-selling category.',
                        ],
                        [
                            'label'   => 'Translations + Slugs',
                            'purpose' => 'Localized category naming and URL friendliness.',
                            'how'     => 'Fill names/descriptions/slugs per active language.',
                            'example' => 'EN slug: /flowers, UK slug: /kvity.',
                        ],
                    ],
                ],
            ],
        ],

        'catalog_attributes' => [
            'title'             => 'Wiki: Catalog / Attributes',
            'navigation_label'  => 'Catalog: Attributes',
            'description'       => 'Guide for attribute dictionary used by product forms and filters.',
            'intro_title'       => 'How to manage attributes',
            'intro_description' => 'Attributes are reusable entities for product characteristics and filtering UX.',
            'examples'          => [
                'Create reusable attribute entities before filling products.',
                'Always fill localized labels for active languages.',
            ],
            'sections' => [
                [
                    'title'                  => 'Attribute setup',
                    'description'            => 'Main attribute list and editing workflow.',
                    'screenshot'             => 'main.png',
                    'screenshot_description' => 'Attributes list in admin panel.',
                    'fields'                 => [
                        [
                            'label'   => 'Name + Active',
                            'purpose' => 'Controls attribute availability in product forms.',
                            'how'     => 'Use concise semantic names and keep active status explicit.',
                            'example' => 'Name: “Color”, Active=true.',
                        ],
                        [
                            'label'   => 'Sort order',
                            'purpose' => 'Defines display order where attributes are rendered.',
                            'how'     => 'Assign deterministic order for stable forms and filters.',
                            'example' => 'Color=10, Size=20.',
                        ],
                        [
                            'label'   => 'Translations',
                            'purpose' => 'Prevents mixed-language attribute labels on storefront.',
                            'how'     => 'Fill each active language tab.',
                            'example' => 'EN: “Color”, UK: “Колір”.',
                        ],
                    ],
                ],
            ],
        ],

        'info_pages' => [
            'title'             => 'Wiki: Info Pages',
            'navigation_label'  => 'Info Pages',
            'description'       => 'Guide for static informational pages and SEO metadata.',
            'intro_title'       => 'How to manage info pages',
            'intro_description' => 'Info pages are content-driven routes with localized body and SEO settings.',
            'examples'          => [
                'Use noindex for utility/legal pages that should not rank.',
                'Fill meta fields and slugs per language.',
            ],
            'sections' => [
                [
                    'title'                  => 'Info page lifecycle',
                    'description'            => 'Main fields required for page publication.',
                    'screenshot'             => 'main.png',
                    'screenshot_description' => 'Info pages list and actions.',
                    'fields'                 => [
                        [
                            'label'   => 'Title + Description',
                            'purpose' => 'Core page content shown to users.',
                            'how'     => 'Fill localized tabs with complete and readable text.',
                            'example' => 'Delivery page with EN and UK localized content.',
                        ],
                        [
                            'label'   => 'Meta title / description / keywords',
                            'purpose' => 'Controls SEO snippet behavior in search engines.',
                            'how'     => 'Avoid duplicate meta values across languages.',
                            'example' => 'Meta title: “Delivery terms in Kyiv”.',
                        ],
                        [
                            'label'   => 'Slug + Noindex',
                            'purpose' => 'Defines URL and indexing policy.',
                            'how'     => 'Use unique slug per language; enable noindex when needed.',
                            'example' => 'Slug: /delivery, Noindex=false.',
                        ],
                    ],
                ],
            ],
        ],

        'users' => [
            'title'             => 'Wiki: Users',
            'navigation_label'  => 'Users: Users',
            'description'       => 'Guide for admin users and profile data management.',
            'intro_title'       => 'How to manage users',
            'intro_description' => 'Use this section to create and maintain admin user accounts.',
            'examples'          => [
                'Use unique email and verified contact data.',
                'Rotate passwords when roles are changed.',
            ],
            'sections' => [
                [
                    'title'                  => 'User account fields',
                    'description'            => 'Core user profile and access data.',
                    'screenshot'             => 'main.png',
                    'screenshot_description' => 'Users list and account management entry points.',
                    'fields'                 => [
                        [
                            'label'   => 'Name / Lastname / Email',
                            'purpose' => 'Identity and authentication information.',
                            'how'     => 'Keep email unique and valid for notifications/recovery.',
                            'example' => 'john@example.com for admin account.',
                        ],
                        [
                            'label'   => 'Telephone / Avatar',
                            'purpose' => 'Operational profile data for support workflows.',
                            'how'     => 'Use real contact data for internal communication.',
                            'example' => '+380... and profile avatar image.',
                        ],
                        [
                            'label'   => 'Password fields',
                            'purpose' => 'Credential management for secure access.',
                            'how'     => 'Set strong password and confirm value while editing.',
                            'example' => 'Minimum 12+ chars with letters and digits.',
                        ],
                    ],
                ],
            ],
        ],

        'user_groups' => [
            'title'             => 'Wiki: Users / User Groups',
            'navigation_label'  => 'Users: User Groups',
            'description'       => 'Guide for grouping users by role semantics.',
            'intro_title'       => 'How to manage user groups',
            'intro_description' => 'User groups help structure access and business segmentation.',
            'examples'          => [
                'Separate groups for managers, editors, and support operators.',
            ],
            'sections' => [
                [
                    'title'                  => 'Group management',
                    'description'            => 'Main fields and naming conventions for groups.',
                    'screenshot'             => 'main.png',
                    'screenshot_description' => 'User groups list and actions.',
                    'fields'                 => [
                        [
                            'label'   => 'Group name',
                            'purpose' => 'Human-readable group identity in ACL workflows.',
                            'how'     => 'Use stable names that match your internal permission model.',
                            'example' => 'Content Editors, Sales Managers.',
                        ],
                        [
                            'label'   => 'Description (if used)',
                            'purpose' => 'Documents intended group responsibilities.',
                            'how'     => 'Briefly describe scope and permission boundaries.',
                            'example' => 'Can edit catalog, cannot manage users.',
                        ],
                    ],
                ],
            ],
        ],

        'modules' => [
            'title'             => 'Wiki: Modules',
            'navigation_label'  => 'Modules',
            'description'       => 'Guide for module lifecycle, instance settings, and runtime state.',
            'intro_title'       => 'How to manage modules',
            'intro_description' => 'Modules page controls definition sync, module state, and instance configuration.',
            'examples'          => [
                'Run sync after filesystem module changes.',
                'Use instance settings to tune storefront rendering context.',
            ],
            'sections' => [
                [
                    'title'                  => 'Definitions and instances',
                    'description'            => 'Understand global and per-instance controls.',
                    'screenshot'             => 'main.png',
                    'screenshot_description' => 'Modules grouped list with instance rows.',
                    'fields'                 => [
                        [
                            'label'   => 'Definition state',
                            'purpose' => 'Global enable/disable toggle for module capability.',
                            'how'     => 'Disable definition only when module should be globally inactive.',
                            'example' => 'Disable module in maintenance period.',
                        ],
                        [
                            'label'   => 'Instance state and placement',
                            'purpose' => 'Controls where and how module is rendered.',
                            'how'     => 'Configure each instance by placement/context and sort order.',
                            'example' => 'Home page bottom module with sort order 10.',
                        ],
                        [
                            'label'   => 'Sync modules action',
                            'purpose' => 'Aligns database definitions with filesystem modules.',
                            'how'     => 'Run after adding/removing module packages.',
                            'example' => 'After deploying new custom module.',
                        ],
                    ],
                ],
            ],
        ],

        'application_currencies' => [
            'title'             => 'Wiki: Application Settings / Currencies',
            'navigation_label'  => 'Application Settings: Currencies',
            'description'       => 'Guide for currency configuration and exchange rate updates.',
            'intro_title'       => 'How to manage currencies',
            'intro_description' => 'Currencies control storefront price formatting and conversion base.',
            'examples'          => [
                'Keep exactly one default active currency.',
                'Sync rates before marketing campaigns.',
            ],
            'sections' => [
                [
                    'title'                  => 'Currency settings',
                    'description'            => 'Core fields that affect conversion and display.',
                    'screenshot'             => 'main.png',
                    'screenshot_description' => 'Currencies list page with update rates action.',
                    'fields'                 => [
                        [
                            'label'   => 'Code / Locale / Symbol',
                            'purpose' => 'Formatting and identification of currency values.',
                            'how'     => 'Use ISO-like code and locale that matches display needs.',
                            'example' => 'USD / en_US / $.',
                        ],
                        [
                            'label'   => 'Rate + Default',
                            'purpose' => 'Defines conversion coefficient and base currency.',
                            'how'     => 'Keep one default currency and valid positive rate.',
                            'example' => 'UAH default; USD rate relative to UAH.',
                        ],
                        [
                            'label'   => 'Update rates action',
                            'purpose' => 'Fetches latest rates using integration service.',
                            'how'     => 'Run action and verify successful notification.',
                            'example' => 'Manual rate update after large FX movement.',
                        ],
                    ],
                ],
            ],
        ],

        'application_languages' => [
            'title'             => 'Wiki: Application Settings / Languages',
            'navigation_label'  => 'Application Settings: Languages',
            'description'       => 'Guide for active locales and language defaults.',
            'intro_title'       => 'How to manage languages',
            'intro_description' => 'Languages define available translation tabs and storefront locale selection.',
            'examples'          => [
                'Activate language only when translations are prepared.',
                'Keep one default language for stable fallback.',
            ],
            'sections' => [
                [
                    'title'                  => 'Language settings',
                    'description'            => 'Core language fields and activation logic.',
                    'screenshot'             => 'main.png',
                    'screenshot_description' => 'Languages list and edit controls.',
                    'fields'                 => [
                        [
                            'label'   => 'Name / Code',
                            'purpose' => 'Identifies language in UI and routing/localization contexts.',
                            'how'     => 'Use stable language code conventions used across project.',
                            'example' => 'English / en, Ukrainian / uk.',
                        ],
                        [
                            'label'   => 'Active + Default',
                            'purpose' => 'Controls visibility and fallback locale behavior.',
                            'how'     => 'Keep one default active language.',
                            'example' => 'uk default, en additional active.',
                        ],
                        [
                            'label'   => 'Format locale',
                            'purpose' => 'Defines date/number formatting behavior.',
                            'how'     => 'Set locale code compatible with formatting usage in app.',
                            'example' => 'uk_UA, en_US.',
                        ],
                    ],
                ],
            ],
        ],

        'application_settings' => [
            'title'             => 'Wiki: Application Settings / Global',
            'navigation_label'  => 'Application Settings: Global',
            'description'       => 'Guide for global site configuration values.',
            'intro_title'       => 'How to manage global app settings',
            'intro_description' => 'Global settings provide shared texts, contacts, socials, map and technical options.',
            'examples'          => [
                'Fill localized contact blocks for all active languages.',
                'Validate social links and embedded map after updates.',
            ],
            'sections' => [
                [
                    'title'                  => 'Global settings form',
                    'description'            => 'Localized and technical blocks used across storefront.',
                    'screenshot'             => 'main.png',
                    'screenshot_description' => 'App settings page with localized sections and shared options.',
                    'fields'                 => [
                        [
                            'label'   => 'Localized titles and contacts',
                            'purpose' => 'Content shown in header/footer and contact sections.',
                            'how'     => 'Fill titles, phones, emails, addresses by language tabs.',
                            'example' => 'EN and UK phone/email blocks filled.',
                        ],
                        [
                            'label'   => 'Social links + SVG icons',
                            'purpose' => 'Renders social network links in storefront.',
                            'how'     => 'Use valid URLs and safe SVG markup without scripts.',
                            'example' => 'facebook, instagram, telegram links.',
                        ],
                        [
                            'label'   => 'Timezone + image sizes',
                            'purpose' => 'Controls date behavior and image conversion presets.',
                            'how'     => 'Set timezone once and keep image size presets in sync with frontend needs.',
                            'example' => 'Timezone: Europe/Kyiv, logo preset 197x64.',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
