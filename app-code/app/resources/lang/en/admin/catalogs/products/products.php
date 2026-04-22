<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Products',

    // Labels
    'labels' => [
        'model'                             => 'Product',
        'plural_model'                      => 'Products',
        'variant_model'                     => 'Product Variant',
        'variant_plural_model'              => 'Product Variants',
        'add_discount'                      => 'Add Discount',
        'add_attribute'                     => 'Add Attribute',
        'delete_discount'                   => 'Delete Discount',
        'delete_attribute'                  => 'Delete Attribute',
        'size_guide_title'                  => 'Block / popup title',
        'size_guide_short_description'      => 'Short description',
        'size_guide_table'                  => 'Size table',
        'size_guide_table_helper'           => 'Paste a table in TSV (tab) or CSV (use separator - ;) format. Each new line becomes a table row.',
        'size_guide_table_preview'          => 'Table preview',
        'size_guide_table_preview_empty'    => 'Start entering TSV/CSV and the preview will appear here.',
        'size_guide_image'                  => 'Image',
        'size_guide_full_description_title' => 'Full description block title',
        'size_guide_full_description'       => 'Full description',
        'composition_title'                 => 'Composition block title',
        'composition_items'                 => 'Composition list items',
        'composition_item_value'            => 'Composition item',
        'care_title'                        => 'Care block title',
        'care_items'                        => 'Care list items',
        'care_item_value'                   => 'Care item',
    ],

    // Sections
    'sections' => [
        'size_guide'           => 'Size guide',
        'composition_and_care' => 'Composition and care',
        'composition_block'    => 'Composition block',
        'care_block'           => 'Care block',
    ],

    // Tabs
    'tabs' => [
        'size_guide'           => 'Size guide',
        'composition_and_care' => 'Composition and care',
    ],

    // Pages
    'pages' => [
        'create'         => 'Create Product',
        'edit'           => 'Edit Product',
        'variants'       => 'Product Variants',
        'create_variant' => 'Create Product Variant',
        'edit_variant'   => 'Edit Product Variant',
    ],

    'actions' => [
        'manage_variants' => 'Manage Variants',
        'create_variant'  => 'Create Variant',
        'back_to_product' => 'Back to Product',
    ],

    // Errors
    'errors' => [
        'duplicate_attribute_language'             => 'The combination of attribute and language must be unique!',
        'validation_size_guide_table_required'     => 'Size table is required when an image is provided or table editing has started.',
        'duplicate_variant_slug_language'          => 'Variant slug language cannot be duplicated.',
        'duplicate_variant_slug_value'             => 'This SEO slug for the selected language is already used by another product variant.',
        'duplicate_product_or_variant_slug_value'  => 'This SEO slug is already used by another product or product variant.',
        'duplicate_variant_attributes_combination' => 'A variant with the same set of attributes and values already exists for this product.',
    ],
];
