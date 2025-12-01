<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Users\Schemas;

use App\Models\Users\UserGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $user_group = new UserGroup();

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin/users/users.label_name'))
                    ->maxLength(255)
                    ->placeholder('John')
                    ->rules(['string', 'max:255'])
                    ->required(),

                TextInput::make('lastname')
                    ->label(__('admin/users/users.label_lastname'))
                    ->maxLength(255)
                    ->placeholder('Doe')
                    ->rules(['nullable', 'string', 'max:255'])
                    ->default(null),

                TextInput::make('email')
                    ->label(__('admin/users/users.label_email'))
                    ->maxLength(255)
                    ->placeholder('knur@gamil.com')
                    ->regex(config('app.regex_validate_conditions.email'))
                    ->email()
                    ->rules(['email', 'max:255', Rule::unique('users', 'email')->ignore($schema->getRecord()->id)])
                    ->required(),

                TextInput::make('telephone')
                    ->label(__('admin/users/users.label_telephone'))
                    ->maxLength(20)
                    ->placeholder('+380 (96) 690-64-12')
                    ->telRegex(config('app.regex_validate_conditions.telephone'))
                    ->tel()
                    ->rules(['nullable', 'string', 'max:20', Rule::unique('users', 'telephone')->ignore($schema->getRecord()->id), 'regex:' . config('app.regex_validate_conditions.telephone')])
                    ->default(null),

                FileUpload::make('avatar')
                    ->label(__('admin/users/users.label_avatar'))
                    ->image() // accept images only
                    ->directory(config('path.avatars') . date('Y/m')) // store under avatars folder
                    ->preserveFilenames(false) // generate unique names
                    ->maxSize(5120) // max 5MB
                    ->nullable()
                    ->default(null),

                DateTimePicker::make('email_verified_at')
                    ->label(__('admin/users/users.label_email_verified_at'))
                    ->helperText(__('admin/users/users.helper_email_verified_at'))
                    ->rules(['nullable', 'date'])
                    ->default(null),

                TextInput::make('password')
                    ->label(__('admin/users/users.label_password'))
                    ->helperText(__('admin/users/users.label_password'))
                    ->password()
                    ->rules(['nullable', 'string', 'min:3', 'confirmed', 'regex:' . config('app.regex_validate_conditions.password')])
                    ->default(null),

                TextInput::make('password_confirmation')
                    ->label(__('admin/users/users.label_password_confirmation'))
                    ->password()
                    ->rules(['nullable', 'required_with:password'])
                    ->default(null),

                Toggle::make('is_active')
                    ->label(__('admin/users/users.label_is_active'))
                    ->helperText(__('admin/users/users.helper_is_active'))
                    ->default(false),

                Select::make('user_group_id')
                    ->label(__('admin/users/users.label_user_group'))
                    ->options(function () use ($user_group) {
                        return $user_group->getAllActiveUserGroups()->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->rules(['nullable', 'exists:user_groups,id'])
                    ->required(),
            ]);
    }
}
