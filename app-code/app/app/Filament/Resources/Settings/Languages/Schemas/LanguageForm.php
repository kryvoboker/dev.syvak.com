<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Languages\Schemas;

use App\Filament\Resources\Trait\ToggleCheckboxFormTrait;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LanguageForm
{
    use ToggleCheckboxFormTrait;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('admin/default.labels.code'))
                    ->helperText(__('admin/settings/languages.helpers.code'))
                    ->required()
                    ->maxLength(10)
                    ->unique(ignoreRecord: true)
                    ->placeholder('en')
                    ->rules(['alpha_dash', 'lowercase', 'max:10']),

                TextInput::make('name')
                    ->label(__('admin/default.labels.name'))
                    ->helperText(__('admin/settings/languages.helpers.name'))
                    ->required()
                    ->maxLength(100)
                    ->placeholder('English'),

                self::getIsActiveField([
                    'helper_text' => __('admin/settings/languages.helpers.is_active'),
                    'default'     => false,
                ]),

                self::getIsDefaultField([
                    'helper_text' => __('admin/settings/languages.helpers.is_default'),
                    'default'     => false,
                ]),
            ]);
    }
}
