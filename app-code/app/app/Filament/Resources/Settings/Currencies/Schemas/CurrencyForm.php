<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Currencies\Schemas;

use App\Filament\Resources\Trait\Forms\CommonTextFormTrait;
use App\Filament\Resources\Trait\Forms\NumericFormTrait;
use App\Filament\Resources\Trait\Forms\ToggleCheckboxFormTrait;
use Filament\Schemas\Schema;

class CurrencyForm
{
    use CommonTextFormTrait, ToggleCheckboxFormTrait, NumericFormTrait;

    /**
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::getTextFormField([
                    'field_name'  => 'code',
                    'label'       => __('admin/default.labels.code'),
                    'helper_text' => __('admin/settings/currencies.helpers.code'),
                    'max_length'  => 3,
                    'placeholder' => 'USD',
                    'rules'       => ['alpha', 'uppercase', 'size:3'],
                    'unique'      => ['ignore_record' => true],
                ]),

                self::getTextFormField([
                    'field_name'  => 'name',
                    'label'       => __('admin/default.labels.name'),
                    'helper_text' => __('admin/settings/currencies.helpers.name'),
                    'max_length'  => 100,
                    'placeholder' => 'US Dollar',
                ]),

                self::getTextFormField([
                    'field_name'  => 'format_locale',
                    'label'       => __('admin/default.labels.format_locale'),
                    'helper_text' => __('admin/settings/currencies.helpers.format_locale'),
                    'max_length'  => 10,
                    'placeholder' => 'uk_UA',
                ]),

                self::getTextFormField([
                    'field_name'  => 'symbol_left',
                    'label'       => __('admin/settings/currencies.labels.symbol_left'),
                    'helper_text' => __('admin/settings/currencies.helpers.symbol_left'),
                    'max_length'  => 10,
                    'placeholder' => '$',
                    'required'    => false,
                ]),

                self::getTextFormField([
                    'field_name'  => 'symbol_right',
                    'label'       => __('admin/settings/currencies.labels.symbol_right'),
                    'helper_text' => __('admin/settings/currencies.helpers.symbol_right'),
                    'max_length'  => 10,
                    'placeholder' => '€',
                    'required'    => false,
                ]),

                self::getNumericFormField([
                    'field_name'  => 'decimal_places',
                    'label'       => __('admin/settings/currencies.labels.decimal_places'),
                    'helper_text' => __('admin/settings/currencies.helpers.decimal_places'),
                    'placeholder' => 2,
                    'default'     => 2,
                    'min_value'   => 0,
                    'max_value'   => 4,
                    'required'    => true,
                ]),

                self::getNumericFormField([
                    'field_name'  => 'exchange_rate',
                    'label'       => __('admin/settings/currencies.labels.exchange_rate'),
                    'helper_text' => __('admin/settings/currencies.helpers.exchange_rate'),
                    'placeholder' => 1.000000,
                    'default'     => 1.000000,
                    'min_value'   => 0.000001,
                    'step'        => 0.000001,
                    'required'    => true,
                ]),

                self::getIsActiveFormField([
                    'helper_text' => __('admin/settings/currencies.helpers.is_active'),
                    'default'     => false,
                ]),

                self::getIsDefaultFormField([
                    'helper_text' => __('admin/settings/currencies.helpers.is_default'),
                    'default'     => false,
                ]),
            ]);
    }
}
