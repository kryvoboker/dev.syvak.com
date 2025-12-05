<?php

return [
    // Navigation
    'navigation_label' => 'Currencies',

    // Labels
    'labels'           => [
        'model'          => 'Currency',
        'plural_model'   => 'Currencies',
        'code'           => 'Currency Code',
        'name'           => 'Currency Name',
        'format_locale'  => 'Format Locale',
        'symbol_left'    => 'Symbol Left',
        'symbol_right'   => 'Symbol Right',
        'decimal_places' => 'Decimal Places',
        'exchange_rate'  => 'Exchange Rate',
    ],

    // Helpers
    'helpers'          => [
        'name'           => 'Full currency name (e.g., US Dollar, Euro, Ukrainian Hryvnia)',
        'code'           => 'ISO 4217 code (e.g., USD, EUR, UAH)',
        'format_locale'  => 'Locale for formatting currency (e.g., en_US, de_DE)',
        'symbol_left'    => 'Symbol displayed to the left of the amount (e.g., $)',
        'symbol_right'   => 'Symbol displayed to the right of the amount (e.g., €)',
        'decimal_places' => 'Number of decimal places to display (e.g., 2 for cents)',
        'exchange_rate'  => 'Exchange rate relative to the default currency (e.g., 1.000000)',
        'is_active'      => 'Enable this currency for users',
        'is_default'     => 'Set as default currency for the system',
    ],

    // Columns
    'columns'          => [
        'symbol_left'    => 'Symbol Left',
        'symbol_right'   => 'Symbol Right',
        'decimal_places' => 'Decimal Places',
        'exchange_rate'  => 'Exchange Rate',
    ],

    // Actions
    'actions'          => [
        'update_rates'             => 'Update Rates',
        'modal_update_rates_title' => 'Update Currency Rates',
        'modal_update_rates_body'  => 'The system will request new exchange rates and update active currencies.',
    ],

    // Notifications
    'notifications'    => [
        'rates_updated_body' => 'Exchange rates have been successfully updated.',
    ],

    // Text
    'texts'            => [
        'cant_delete_default_currency'     => 'Cannot delete default currency',
        'cant_delete_last_active_currency' => 'Cannot delete last active currency',
    ],

    // Error
    'errors'           => [
        'cant_delete_default_currency'     => 'Please set another currency as default before deleting this one!',
        'cant_delete_last_active_currency' => 'At least one active currency must remain in the system!',
        'failed_to_update_rates'           => 'Failed to update currency rates. Please try again later!',
        'absent_default_currency'          => 'The system must have a default currency set!',
    ],
];
