<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Languages\Schemas;

use App\Filament\Resources\Trait\Forms\CommonTextFormTrait;
use App\Filament\Resources\Trait\Forms\ToggleCheckboxFormTrait;
use Filament\Schemas\Schema;

class LanguageForm
{
    use CommonTextFormTrait, ToggleCheckboxFormTrait;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::getTextFormField([
                    'field_name'  => 'code',
                    'label'       => __('admin/default.labels.code'),
                    'helper_text' => __('admin/settings/languages.helpers.code'),
                    'max_length'  => 10,
                    'placeholder' => 'en',
                    'rules'       => ['alpha', 'lowercase', 'max:10'],
                    'unique'      => ['ignore_record' => true],
                ]),

                self::getTextFormField([
                    'field_name'  => 'name',
                    'label'       => __('admin/default.labels.name'),
                    'helper_text' => __('admin/settings/languages.helpers.name'),
                    'max_length'  => 100,
                    'placeholder' => 'English',
                ]),

                self::getIsActiveFormField([
                    'helper_text' => __('admin/settings/languages.helpers.is_active'),
                    'default'     => false,
                ]),

                self::getIsDefaultFormField([
                    'helper_text' => __('admin/settings/languages.helpers.is_default'),
                    'default'     => false,
                ]),
            ]);
    }
}
