<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Languages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('admin/default.labels.code'))
                    ->helperText(__('admin/settings/languages.helpers.code'))
                    ->maxLength(10)
                    ->placeholder('en')
                    ->rules(['required', 'alpha', 'lowercase', 'max:10'])
                    ->unique(ignoreRecord: true)
                    ->required(),

                TextInput::make('name')
                    ->label(__('admin/default.labels.name'))
                    ->helperText(__('admin/settings/languages.helpers.name'))
                    ->maxLength(100)
                    ->placeholder('English')
                    ->rules(['required', 'string', 'max:100'])
                    ->required(),

                Toggle::make('is_active')
                    ->label(__('admin/default.labels.is_active'))
                    ->helperText(__('admin/settings/languages.helpers.is_active'))
                    ->default(false)
                    ->required(),

                Toggle::make('is_default')
                    ->label(__('admin/default.labels.is_default'))
                    ->helperText(__('admin/settings/languages.helpers.is_default'))
                    ->default(false)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if ($state) {
                            $set('is_active', true);
                        }
                    })
                    ->required(),
            ]);
    }
}
