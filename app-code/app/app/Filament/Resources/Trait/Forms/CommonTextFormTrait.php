<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trait\Forms;

use App\Filament\Resources\Trait\CommonTrait;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

trait CommonTextFormTrait
{
    use CommonTrait;

    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getTextFormField(array $params = []): Field
    {
        $max_length  = self::processGetMaxValue($params);
        $is_required = $params['required'] ?? true;
        $rules       = $params['rules'] ?? ['string'];

        if ($is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($max_length) {
            $rules[] = "max:$max_length";
        }

        self::uniqulizeArr($rules);

        $text_input = TextInput::make($params['field_name'])
            ->label($params['label'])
            ->helperText($params['helper_text'] ?? null)
            ->maxLength($max_length)
            ->placeholder($params['placeholder'] ?? null)
            ->rules($rules)
            ->default($params['default'] ?? null)
            ->required($is_required);

        if (isset($params['unique']['ignore_record'])) {
            $text_input->unique(ignoreRecord: $params['unique']['ignore_record']);
        }

        if (isset($params['is_column_span_full']) && $params['is_column_span_full'] === true) {
            $text_input->columnSpanFull();
        }

        return $text_input;
    }

    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getTextAreaFormField(array $params = []): Field
    {
        $max_length  = self::processGetMaxValue($params);
        $is_required = $params['required'] ?? false;
        $rules       = $params['rules'] ?? ['string'];

        if ($is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($max_length) {
            $rules[] = "max:$max_length";
        }

        self::uniqulizeArr($rules);

        $text_area = Textarea::make($params['field_name'])
            ->label($params['label'])
            ->helperText($params['helper_text'] ?? null)
            ->placeholder($params['placeholder'] ?? null)
            ->rules($rules)
            ->rows($params['rows'] ?? 4)
            ->cols($params['cols'] ?? null)
            ->maxLength($max_length)
            ->required($is_required)
            ->default($params['default'] ?? null);

        if (isset($params['is_column_span_full']) && $params['is_column_span_full'] === true) {
            $text_area->columnSpanFull();
        } else if (isset($params['column_span']) && is_int($params['column_span']) && $params['column_span'] > 0) {
            $text_area->columnSpan($params['column_span']);
        }

        return $text_area;
    }

    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getEmailFormField(array $params = []): Field
    {
        $max_length  = self::processGetMaxValue($params);
        $is_required = $params['required'] ?? true;
        $rules       = $params['rules'] ?? ['email'];

        if ($is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($max_length) {
            $rules[] = "max:$max_length";
        }

        self::uniqulizeArr($rules);

        $text_input = TextInput::make($params['field_name'] ?? 'email')
            ->label($params['label'] ?? __('admin/default.labels.email'))
            ->helperText($params['helper_text'] ?? null)
            ->maxLength($max_length)
            ->placeholder($params['placeholder'] ?? 'knur@gamil.com')
            ->regex($params['regex'] ?? config('app.regex_validate_conditions.email'))
            ->email()
            ->rules($rules)
            ->required($is_required)
            ->default($params['default'] ?? null);

        if (isset($params['unique']['ignore_record'])) {
            $text_input->unique(ignoreRecord: $params['unique']['ignore_record']);
        }

        if (isset($params['is_column_span_full']) && $params['is_column_span_full'] === true) {
            $text_input->columnSpanFull();
        }

        return $text_input;
    }

    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getTelFormField(array $params = []): Field
    {
        $max_length  = self::processGetMaxValue($params);
        $is_required = $params['required'] ?? true;
        $rules       = $params['rules'] ?? ['string', 'regex:' . config('app.regex_validate_conditions.telephone')];

        if ($is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($max_length) {
            $rules[] = "max:$max_length";
        }

        self::uniqulizeArr($rules);

        $text_input = TextInput::make($params['field_name'] ?? 'telephone')
            ->label($params['label'] ?? __('admin/default.labels.telephone'))
            ->helperText($params['helper_text'] ?? null)
            ->maxLength($max_length)
            ->placeholder($params['placeholder'] ?? '+380 (96) 690-64-12')
            ->regex($params['regex'] ?? config('app.regex_validate_conditions.telephone'))
            ->tel()
            ->rules($rules)
            ->required($is_required)
            ->default($params['default'] ?? null);

        if (isset($params['unique']['ignore_record'])) {
            $text_input->unique(ignoreRecord: $params['unique']['ignore_record']);
        }

        if (isset($params['is_column_span_full']) && $params['is_column_span_full'] === true) {
            $text_input->columnSpanFull();
        }

        return $text_input;
    }

    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getUrlFormField(array $params = []): Field
    {
        $max_length  = self::processGetMaxValue($params);
        $is_required = $params['required'] ?? true;
        $rules       = $params['rules'] ?? ['url'];

        if ($is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($max_length) {
            $rules[] = "max:$max_length";
        }

        self::uniqulizeArr($rules);

        $url_input = TextInput::make($params['field_name'] ?? 'url')
            ->label($params['label'] ?? __('admin/default.labels.url'))
            ->helperText($params['helper_text'] ?? null)
            ->maxLength($max_length)
            ->placeholder($params['placeholder'] ?? 'https://example.com')
            ->rules($rules)
            ->url()
            ->required($is_required)
            ->default($params['default'] ?? null);

        if (isset($params['unique']['ignore_record'])) {
            $url_input->unique(ignoreRecord: $params['unique']['ignore_record']);
        }

        if (isset($params['is_column_span_full']) && $params['is_column_span_full'] === true) {
            $url_input->columnSpanFull();
        }

        return $url_input;
    }
}
