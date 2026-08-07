<?php

declare(strict_types=1);

return [
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'text' => 'Message',
        'file' => 'File',
    ],
    'placeholders' => [
        'name' => 'Your name',
        'email' => 'your@email.com',
        'phone' => '+38 000 000 00 00',
        'text' => 'Your message',
    ],
    'labels' => [
        'map' => 'Map',
        'images' => 'Contacts page images',
        'contact_information' => 'Contact information',
        'contact_details' => 'Contact details',
    ],
    'buttons' => [
        'submit' => 'Send message',
        'open_map' => 'Open map',
        'open_address' => 'Open address',
        'close_error' => 'Close error',
    ],
    'validation' => [
        'invalid' => 'Please enter a valid value.',
        'invalid_file' => 'Please select a valid file.',
    ],
    'messages' => [
        'success' => 'Your message has been sent successfully.',
    ],
    'errors' => [
        'delivery_failed' => 'The message could not be sent. Please try again later.',
        'page_not_found' => 'The Contacts page was not found.',
    ],
    'fallbacks' => [
        'title' => 'Contacts',
        'working_hours_title' => 'Working hours',
    ],
];
