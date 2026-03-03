<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trait\Forms;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;

trait NumericFormTrait
{
    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getNumericFormField(array $params = []): Field
    {
        $max_value      = $params['max_value '] ?? null;
        $is_required    = $params['required'] ?? true;
        $numeric_params = $params['numeric_params'] ?? [];
        $rules          = $params['rules'] ?? ['numeric'];

        if ($is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($max_value !== null) {
            $rules[] = "max:$max_value";
        }

        $numeric_input = TextInput::make($params['field_name'])
            ->label($params['label'])
            ->numeric(...$numeric_params)
            ->rules($rules)
            ->minValue($params['min_value'] ?? 0)
            ->maxValue($max_value)
            ->disabled($params['disabled'] ?? false)
            ->dehydrated($params['dehydrated'] ?? true)
            ->step($params['step'] ?? null)
            ->default($params['default'] ?? 0)
            ->required($is_required);

        if (isset($params['unique']['ignore_record'])) {
            $numeric_input->unique(ignoreRecord: $params['unique']['ignore_record']);
        }

        if (isset($params['is_column_span_full']) && $params['is_column_span_full'] === true) {
            $numeric_input->columnSpanFull();
        }

        return $numeric_input;
    }

    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getPriceFormField(array $params = []): Field
    {
        $max_value      = $params['max_value'] ?? null;
        $is_required    = $params['required'] ?? true;
        $numeric_params = $params['numeric_params'] ?? [];
        $rules          = $params['rules'] ?? ['numeric'];

        if ($is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($max_value !== null) {
            $rules[] = "max:$max_value";
        }

        $numeric_input = TextInput::make($params['field_name'] ?? 'price')
            ->label($params['label'] ?? __('admin/default.labels.price'))
            ->numeric(...$numeric_params)
            ->rules($rules)
            ->minValue($params['min_value'] ?? 0)
            ->maxValue($max_value)
            ->prefix($params['prefix'] ?? config('app.currency.default_currency_symbol'))
            ->default($params['default'] ?? 0.0)
            ->required($is_required);

        if (isset($params['unique']['ignore_record'])) {
            $numeric_input->unique(ignoreRecord: $params['unique']['ignore_record']);
        }

        if (isset($params['is_column_span_full']) && $params['is_column_span_full'] === true) {
            $numeric_input->columnSpanFull();
        }

        return $numeric_input;
    }

    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getEanField(array $params = []): Field
    {
        $max_digits     = (int)config('app.products.ean_max_length');
        $is_required    = $params['required'] ?? true;
        $numeric_params = $params['numeric_params'] ?? [];
        $rules          = $params['rules'] ?? ['numeric'];

        if ($is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($max_digits) {
            $rules[] = "max_digits:$max_digits";
        }

        self::uniqulizeArr($rules);

        $text_input = TextInput::make($params['field_name'] ?? 'ean')
            ->label($params['label'] ?? __('admin/default.labels.ean'))
            ->helperText($params['helper_text'] ?? null)
            ->placeholder($params['placeholder'] ?? null)
            ->rules($rules)
            ->numeric(...$numeric_params)
            ->minValue($params['min_value'] ?? 0)
            ->default($params['default'] ?? null)
            ->nullable($params['nullable'] ?? !$is_required)
            ->required($is_required);

        if (isset($params['unique']['ignore_record'])) {
            $text_input->unique(ignoreRecord: $params['unique']['ignore_record']);
        }

        if (isset($params['is_column_span_full']) && $params['is_column_span_full'] === true) {
            $text_input->columnSpanFull();
        }

        return $text_input;
    }
}
