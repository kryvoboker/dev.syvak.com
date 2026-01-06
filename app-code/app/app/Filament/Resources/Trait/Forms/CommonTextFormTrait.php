<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trait\Forms;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;

trait CommonTextFormTrait
{
    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getTextFormField(array $params = []): Field
    {
        if (isset($params['max_length']) && is_numeric($params['max_length']) && $params['max_length'] > 0) {
            $max_length = (int)$params['max_length'];
        } else if (isset($params['max_length']) === false) {
            $max_length = null;
        } else {
            $max_length = 255;
        }

        return TextInput::make($params['field_name'])
            ->label($params['label'])
            ->helperText($params['helper_text'] ?? null)
            ->maxLength($max_length)
            ->placeholder($params['placeholder'])
            ->rules($params['rules'] ?? ['string', 'max:255'])
            ->unique(ignoreRecord: $params['unique']['ignore_record'] ?? null)
            ->default($params['default'] ?? null)
            ->required($params['required'] ?? true);
    }

    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getEmailFormField(array $params = []): Field
    {
        if (isset($params['max_length']) && is_numeric($params['max_length']) && $params['max_length'] > 0) {
            $max_length = (int)$params['max_length'];
        } else if (isset($params['max_length']) === false) {
            $max_length = null;
        } else {
            $max_length = 255;
        }

        return TextInput::make($params['field_name'] ?? 'email')
            ->label($params['label'] ?? __('admin/default.labels.email'))
            ->helperText($params['helper_text'] ?? null)
            ->maxLength($max_length)
            ->placeholder($params['placeholder'] ?? 'knur@gamil.com')
            ->regex($params['regex'] ?? config('app.regex_validate_conditions.email'))
            ->email()
            ->rules($params['rules'] ?? ['email', 'max:255'])
            ->required($params['required'] ?? true)
            ->default($params['default'] ?? null);
    }

    /**
     * @param array $params
     *
     * @return Field
     */
    protected static function getTelFormField(array $params = []): Field
    {
        if (isset($params['max_length']) && is_numeric($params['max_length']) && $params['max_length'] > 0) {
            $max_length = (int)$params['max_length'];
        } else if (isset($params['max_length']) === false) {
            $max_length = null;
        } else {
            $max_length = 255;
        }

        return TextInput::make($params['field_name'] ?? 'telephone')
            ->label($params['label'] ?? __('admin/default.labels.telephone'))
            ->helperText($params['helper_text'] ?? null)
            ->maxLength($max_length)
            ->placeholder($params['placeholder'] ?? '+380 (96) 690-64-12')
            ->regex($params['regex'] ?? config('app.regex_validate_conditions.telephone'))
            ->tel()
            ->rules($params['rules'] ?? ['nullable', 'string', 'max:20', 'regex:' . config('app.regex_validate_conditions.telephone')])
            ->required($params['required'] ?? false)
            ->default($params['default'] ?? null);
    }
}
