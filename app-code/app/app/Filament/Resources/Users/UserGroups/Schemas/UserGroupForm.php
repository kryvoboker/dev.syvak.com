<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups\Schemas;

use App\Filament\Resources\Trait\Forms\CommonTextFormTrait;
use App\Filament\Resources\Trait\Forms\ToggleCheckboxFormTrait;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserGroupForm
{
    use CommonTextFormTrait, ToggleCheckboxFormTrait;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::getNameFormField([
                    'placeholder' => 'RRC Users',
                ]),

                Textarea::make('description')
                    ->default(null)
                    ->rows(5)
                    ->columnSpanFull(),

                self::getIsActiveFormField([
                    'helper_text' => __('admin/users/user_groups.helpers.is_active'),
                    'default'     => false,
                ]),

                self::getIsDefaultFormField([
                    'helper_text' => __('admin/users/user_groups.helpers.is_default'),
                    'default'     => false,
                ]),
            ]);
    }
}
