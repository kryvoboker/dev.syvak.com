<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups\Schemas;

use App\Filament\Resources\Trait\ToggleCheckboxFormTrait;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserGroupForm
{
    use ToggleCheckboxFormTrait;

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

                self::getIsActiveField([
                    'helper_text' => __('admin/users/user_groups.helpers.is_active'),
                    'default'     => false,
                ]),

                self::getIsDefaultField([
                    'helper_text' => __('admin/users/user_groups.helpers.is_default'),
                    'default'     => false,
                ]),
            ]);
    }
}
