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
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $user_group = new UserGroup();
        /** @var User|null $record */
        $record = $schema->getRecord();
        $user_image_settings = self::resolveUserImageSettings();
        $user_upload_max_size_kb = max(1, (int) data_get($user_image_settings, 'upload.max_size_kb', (int) config('app.images.user.upload.max_size_kb')));
        $user_upload_max_size_mb = self::resolveMegabytesFromKilobytes($user_upload_max_size_kb);
        $user_image_path = resolve_upload_path_placeholders((string) data_get($user_image_settings, 'image_path', (string) config('app.images.user.image_path', 'images/avatars/' . date('Y/m'))));
        $user_preview_page_width = max(1, (int) data_get($user_image_settings, 'preview_in_page_in_admin.width', (int) config('app.images.user.preview_in_page_in_admin.width')));
        $user_preview_page_height = max(1, (int) data_get($user_image_settings, 'preview_in_page_in_admin.height', (int) config('app.images.user.preview_in_page_in_admin.height')));

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
                    ->helperText(__('admin/default.helpers.max_upload_size_mb', ['size' => $user_upload_max_size_mb]))
                    ->image()
                    ->directory($user_image_path)
                    ->maxSize($user_upload_max_size_kb)
                    ->imagePreviewHeight('250')
                    ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png'])
                    ->mimeTypeMap([
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                    ])
                    ->rules([
                        'nullable',
                        Rule::file()::types(['image/jpeg', 'image/jpg', 'image/png'])->max($user_upload_max_size_kb),
                    ])
                    ->storeFileNamesIn('avatar_file_name')
                    ->imageEditor()
                    ->imageEditorViewportWidth($user_preview_page_width)
                    ->imageEditorViewportHeight($user_preview_page_height)
                    ->imageEditorAspectRatioOptions([
                        '1:1' => '1:1',
                        '4:3' => '4:3',
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

    /**
     * @return array<string, mixed>
     */
    private static function resolveUserImageSettings(): array
    {
        $app_settings = get_app_settings();
        $settings = data_get($app_settings, 'user_settings');

        if ($settings instanceof Collection) {
            return $settings->toArray();
        }

        if (is_array($settings)) {
            return $settings;
        }

        return [];
    }

    private static function resolveMegabytesFromKilobytes(int $kilobytes): string
    {
        return number_format(max(1, $kilobytes) / 1024, 2, '.', '');
    }
}
