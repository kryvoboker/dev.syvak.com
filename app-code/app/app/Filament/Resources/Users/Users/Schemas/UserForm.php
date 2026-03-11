<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Users\Schemas;

use App\Models\Users\User;
use App\Models\Users\UserGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $user_group = new UserGroup();
        /** @var User|null $record */
        $record = $schema->getRecord();

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin/default.labels.name'))
                    ->maxLength(255)
                    ->placeholder('John')
                    ->rules(['required', 'string', 'max:255'])
                    ->required(),

                TextInput::make('lastname')
                    ->label(__('admin/default.labels.lastname'))
                    ->maxLength(255)
                    ->placeholder('Doe')
                    ->rules(['nullable', 'string', 'max:255'])
                    ->nullable(),

                TextInput::make('email')
                    ->label(__('admin/default.labels.email'))
                    ->maxLength(255)
                    ->placeholder('knur@gamil.com')
                    ->regex(config('app.regex_validate_conditions.email'))
                    ->email()
                    ->rules(['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($record?->id)])
                    ->required(),

                TextInput::make('telephone')
                    ->label(__('admin/default.labels.telephone'))
                    ->placeholder('+380 (96) 690-64-12')
                    ->maxLength(20)
                    ->regex(config('app.regex_validate_conditions.telephone'))
                    ->tel()
                    ->rules(['nullable', 'string', 'max:20', Rule::unique('users', 'telephone')->ignore($record?->id), 'regex:' . config('app.regex_validate_conditions.telephone')])
                    ->nullable(),

                FileUpload::make('avatar')
                    ->label(__('admin/default.labels.avatar'))
                    ->image()
                    ->directory(config('app.images.user.image_path'))
                    ->maxSize((int)config('app.images.user.upload.max_size_kb'))
                    ->imagePreviewHeight('250')
                    ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png'])
                    ->mimeTypeMap([
                        'jpg'  => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png'  => 'image/png',
                    ])
                    ->rules([
                        'nullable',
                        Rule::file()::types(['image/jpeg', 'image/jpg', 'image/png'])->max((int) config('app.images.user.upload.max_size_kb')),
                    ])
                    ->storeFileNamesIn('avatar_file_name')
                    ->imageEditor()
                    ->imageEditorViewportWidth((int)config('app.images.user.preview_in_page_in_admin.width'))
                    ->imageEditorViewportHeight((int)config('app.images.user.preview_in_page_in_admin.height'))
                    ->imageEditorAspectRatioOptions([
                        '1:1'  => '1:1',
                        '4:3'  => '4:3',
                        '16:9' => '16:9',
                    ])
                    ->nullable(),

                DateTimePicker::make('email_verified_at')
                    ->label(__('admin/default.labels.email_verified_at'))
                    ->rules(['nullable', 'date'])
                    ->default(now(config('app.timezone')))
                    ->required(false),

                TextInput::make('password')
                    ->label(__('admin/default.labels.password'))
                    ->helperText(__('admin/users/users.helpers.password'))
                    ->password()
                    ->rules(['nullable', 'string', 'min:3', 'confirmed', 'regex:' . config('app.regex_validate_conditions.password')])
                    ->default(null),

                TextInput::make('password_confirmation')
                    ->label(__('admin/default.labels.password_confirmation'))
                    ->password()
                    ->rules(['nullable', 'required_with:password', 'confirmed'])
                    ->default(null),

                Toggle::make('is_active')
                    ->label(__('admin/default.labels.is_active'))
                    ->helperText(__('admin/users/users.helpers.is_active'))
                    ->default(false)
                    ->required(),

                Select::make('user_group_id')
                    ->label(__('admin/default.labels.user_group'))
                    ->options(function () use ($user_group) {
                        return $user_group->getAllActiveUserGroups()->pluck('name', 'id');
                    })
                    ->searchable()
                    ->nullable()
                    ->preload()
                    ->live()
                    ->rules([Rule::exists('user_groups', 'id')]),
            ]);
    }
}
