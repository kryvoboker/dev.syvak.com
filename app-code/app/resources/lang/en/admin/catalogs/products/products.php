<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Products',

    // Labels
    'labels' => [
        'model'                => 'Product',
        'plural_model'         => 'Products',
        'variant_model'        => 'Product Variant',
        'variant_plural_model' => 'Product Variants',
        'add_discount'         => 'Add Discount',
        'add_attribute'        => 'Add Attribute',
        'delete_discount'      => 'Delete Discount',
        'delete_attribute'     => 'Delete Attribute',
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
        'duplicate_attribute_language' => 'The combination of attribute and language must be unique!',
    ],
];
