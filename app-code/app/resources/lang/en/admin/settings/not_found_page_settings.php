<?php

declare(strict_types=1);

return [
    'navigation_label' => '404 Page',

    'tabs' => [
        'general' => 'General Settings',
        'images' => 'Images',
        'slugs' => 'SEO URL',
    ],

    'sections' => [
        'localized_content' => 'Localized Content',
        'home_link' => 'Page Link',
        'slot_1' => 'Image 1',
        'slot_2' => 'Image 2',
    ],

    'labels' => [
        'model' => '404 Page Setting',
        'plural_model' => '404 Page Settings',
        'title' => 'Page title',
        'description' => 'Page description',
        'link_label' => 'Link text',
        'link_url' => 'Link address',
        'image' => 'Image',
        'width' => 'Width',
        'height' => 'Height',
        'is_square' => 'Square image',
        'background' => 'Converted image background',
        'custom_css_classes' => 'Custom CSS Classes',
        'sort_order' => 'Sort order',
    ],

    'helpers' => [
        'link_url' => 'Use an absolute HTTP(S) address or a relative path starting with /. The address is required for every language.',
        'image' => 'Optional image. Supported formats: JPEG, PNG, and SVG. Maximum size: 5 MB.',
        'background' => 'Enter transparent or a six-digit HEX color, for example #FFFFFF.',
        'custom_css_classes' => 'Enter CSS classes separated by spaces.',
    ],

    'defaults' => [
        'home_link_label' => 'Go to the home page',
    ],

    'errors' => [
        'duplicate_slug' => 'This SEO URL is already used for the selected language.',
    ],
];
