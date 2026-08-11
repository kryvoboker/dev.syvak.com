<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Failure Page',
    'tabs' => [
        'images' => 'Images',
        'seo_url' => 'SEO URL',
        'buttons_and_payments' => 'Buttons and payments',
        'support_contacts' => 'Support Contacts',
        'general_settings' => 'General Settings',
    ],
    'sections' => [
        'localized_content' => 'Localized Content',
        'retry_button' => 'Try again button',
        'alternative_payment_button' => 'Alternative payment button',
        'available_payment_methods' => 'Available active payment methods',
        'working_hours' => 'Working hours',
        'phones' => 'Phone numbers',
        'emails' => 'Email addresses',
    ],
    'labels' => [
        'model' => 'Failure Page Setting',
        'plural_model' => 'Failure Page Settings',
        'title' => 'Page title',
        'description' => 'Page description',
        'images' => 'Failure page images',
        'image' => 'Image',
        'width' => 'Width',
        'height' => 'Height',
        'is_square' => 'Square image',
        'background' => 'Converted image background',
        'custom_css_classes' => 'Custom CSS Classes',
        'enabled' => 'Enabled',
        'button_text' => 'Button text',
        'use_contacts_data' => 'Use data from the Contacts page',
        'working_hours' => 'Working hours',
        'phones' => 'Phone numbers',
        'phone' => 'Phone number',
        'phone_type' => 'Phone type',
        'emails' => 'Email addresses',
        'email' => 'Email address',
    ],
    'helpers' => [
        'image' => 'Optional image. Supported formats: JPEG, PNG, and SVG.',
        'background' => 'Enter transparent or a six-digit HEX color, for example #FFFFFF.',
        'custom_css_classes' => 'Enter CSS classes separated by spaces.',
        'use_contacts_working_hours' => 'When enabled, the working hours from the Contacts page are displayed.',
    ],
    'options' => [
        'mobile' => 'Mobile',
        'landline' => 'Landline',
    ],
    'defaults' => [
        'title' => 'Something went wrong...',
        'description' => 'Unfortunately, we could not process the payment. Your card was not charged.',
        'retry_button' => 'Try again',
        'alternative_payment_button' => 'Choose another payment method',
    ],
    'errors' => [
        'duplicate_slug' => 'This SEO URL is already used for the selected language.',
    ],
];
