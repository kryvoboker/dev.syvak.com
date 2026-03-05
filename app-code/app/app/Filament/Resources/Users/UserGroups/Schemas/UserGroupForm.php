<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin/default.labels.name'))
                    ->maxLength(255)
                    ->placeholder('RRC Users')
                    ->rules(['required', 'string', 'max:255'])
                    ->required(),

                Textarea::make('description')
                    ->label(__('admin/default.labels.description'))
                    ->rows(5)
                    ->maxLength(1000)
                    ->rules(['nullable', 'string', 'max:1000'])
                    ->columnSpanFull()
                    ->nullable(),

                Toggle::make('is_active')
                    ->label(__('admin/default.labels.is_active'))
                    ->helperText(__('admin/users/user_groups.helpers.is_active'))
                    ->default(false)
                    ->required(),

                Toggle::make('is_default')
                    ->label(__('admin/default.labels.is_default'))
                    ->helperText(__('admin/users/user_groups.helpers.is_default'))
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
