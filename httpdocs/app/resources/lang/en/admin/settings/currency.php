<?php

return [
    // Navigation
    'navigation_label'                       => 'Currencies',

    // Labels
    'label_model'                            => 'Currency',
    'label_plural_model'                     => 'Currencies',
    'label_code'                             => 'Currency Code',
    'label_name'                             => 'Currency Name',
    'label_symbol_left'                      => 'Symbol Left',
    'label_symbol_right'                     => 'Symbol Right',
    'label_decimal_places'                   => 'Decimal Places',
    'label_exchange_rate'                    => 'Exchange Rate',
    'label_is_active'                        => 'Is Active',
    'label_is_default'                       => 'Is Default Currency',

    // Helpers
    'helper_code'                            => 'ISO 4217 code (e.g., USD, EUR, UAH)',
    'helper_name'                            => 'Full currency name (e.g., US Dollar, Euro)',
    'helper_symbol_left'                     => 'Symbol displayed to the left of the amount (e.g., $)',
    'helper_symbol_right'                    => 'Symbol displayed to the right of the amount (e.g., €)',
    'helper_decimal_places'                  => 'Number of decimal places to display (e.g., 2 for cents)',
    'helper_exchange_rate'                   => 'Exchange rate relative to the default currency (e.g., 1.000000)',
    'helper_is_active'                       => 'Enable this currency for users',
    'helper_is_default'                      => 'Set as default currency for the system',

    // Columns
    'column_code'                            => 'Code',
    'column_name'                            => 'Name',
    'column_symbol_left'                     => 'Symbol Left',
    'column_symbol_right'                    => 'Symbol Right',
    'column_decimal_places'                  => 'Decimal Places',
    'column_exchange_rate'                   => 'Exchange Rate',
    'column_active'                          => 'Active',
    'column_default'                         => 'Default',
    'column_created_at'                      => 'Created',

    // Filters
    'filter_active'                          => 'Active Currencies',
    'filter_default'                         => 'Default Currency',
    'placeholder_all_currencies'             => 'All Currencies',
    'true_label_active_only'                 => 'Active only',
    'false_label_inactive_only'              => 'Inactive only',
    'true_label_default_only'                => 'Default only',

    // Actions
    'action_update_rates'                    => 'Update rates',
    'modal_update_rates_title'               => 'Update currency rates',
    'modal_update_rates_body'                => 'The system will request new exchange rates and update active currencies.',
    'notification_rates_updated_title'       => 'Currency rates updated',
    'notification_rates_updated_body'        => 'Exchange rates have been successfully updated.',

    // Text
    'text_cant_delete_default_currency'      => 'Cannot delete default currency',
    'text_cant_delete_last_active_currency'  => 'Cannot delete last active currency',

    // Error
    'error_cant_delete_default_currency'     => 'Please set another currency as default before deleting this one!',
    'error_cant_delete_last_active_currency' => 'At least one active currency must remain in the system!',
    'error_failed_to_update_rates'           => 'Failed to update currency rates. Please try again later!',
    'error_absent_default_currency'          => 'The system must have a default currency set!',
];
