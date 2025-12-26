<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                    ->rules(['string', 'max:255'])
                    ->required(),

                Textarea::make('description')
                    ->default(null)
                    ->rows(5)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label(__('admin/default.labels.is_active'))
                    ->helperText(__('admin/users/user_groups.helpers.is_active'))
                    ->default(false),

                Toggle::make('is_default')
                    ->label(__('admin/default.labels.is_default'))
                    ->helperText(__('admin/users/user_groups.helpers.is_default'))
                    ->default(false),
            ]);
    }
}
