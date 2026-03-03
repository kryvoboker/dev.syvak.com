<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups\Schemas;

use App\Filament\Resources\Trait\Forms\CommonTextFormTrait;
use App\Filament\Resources\Trait\Forms\ToggleCheckboxFormTrait;
use Filament\Schemas\Schema;

class UserGroupForm
{
    use CommonTextFormTrait, ToggleCheckboxFormTrait;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::getTextFormField([
                    'field_name'  => 'name',
                    'label'       => __('admin/default.labels.name'),
                    'max_length'  => 255,
                    'placeholder' => 'RRC Users',
                ]),

                self::getTextAreaFormField([
                    'field_name'          => 'description',
                    'label'               => __('admin/default.labels.description'),
                    'max_length'          => 1000,
                    'rows'                => 5,
                    'is_column_span_full' => true,
                ]),

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
