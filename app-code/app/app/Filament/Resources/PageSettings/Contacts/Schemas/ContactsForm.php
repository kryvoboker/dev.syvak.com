<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Contacts\Schemas;

use App\Filament\Resources\Trait\Forms\SlugFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\ApplicationSettings\Language;
use App\Rules\ValidRegexMask;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

class ContactsForm
{
    use LanguageTrait;
    use SlugFormTrait;

    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getActiveLanguages();

        return $schema
            ->components([
                Tabs::make('ContactsPageSettingsTabs')
                    ->tabs([
                        self::createGeneralTab($active_languages),
                        self::createImagesTab(),
                        self::createSlugsFormTabs(
                            $active_languages,
                            __('admin/settings/contacts_page_settings.tabs.slugs'),
                        ),
                        self::createContactFormTab(),
                        self::createEmailTab($active_languages),
                        self::createTelegramTab($active_languages),
                        self::createMapTab(),
                        self::createAddressTab($active_languages),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    private static function createGeneralTab(Collection $active_languages): Tabs\Tab
    {
        $localized_tabs = $active_languages->map(function (Language $language): Tabs\Tab {
            $language_id = (string) $language->id;

            return Tabs\Tab::make($language->name)
                ->badge($language->code)
                ->schema([
                    TextInput::make("localized.$language_id.title")
                        ->label(__('admin/settings/contacts_page_settings.labels.title'))
                        ->helperText(__('admin/settings/contacts_page_settings.helpers.title'))
                        ->required()
                        ->maxLength(255),

                    Section::make(__('admin/settings/contacts_page_settings.sections.working_hours'))
                        ->schema([
                            TextInput::make("localized.$language_id.working_hours.title")
                                ->label(__('admin/settings/contacts_page_settings.labels.working_hours_title'))
                                ->required()
                                ->maxLength(255),

                            TextInput::make("localized.$language_id.working_hours.description")
                                ->label(__('admin/settings/contacts_page_settings.labels.working_hours_description'))
                                ->nullable()
                                ->maxLength(500),

                            Textarea::make("localized.$language_id.working_hours.content")
                                ->label(__('admin/settings/contacts_page_settings.labels.working_hours_content'))
                                ->rows(5)
                                ->required(),
                        ])
                        ->columns(1),
                ])
                ->columns(1);
        })->all();

        return Tabs\Tab::make(__('admin/settings/contacts_page_settings.tabs.general'))
            ->schema([
                Section::make(__('admin/settings/contacts_page_settings.sections.localized_content'))
                    ->schema([
                        Tabs::make('ContactsLocalizedContentTabs')
                            ->tabs($localized_tabs)
                            ->activeTab(1)
                            ->contained(false),
                    ])
                    ->columnSpanFull(),

                Section::make(__('admin/settings/contacts_page_settings.sections.phones'))
                    ->schema([
                        Repeater::make('phones')
                            ->label(__('admin/settings/contacts_page_settings.labels.phones'))
                            ->helperText(__('admin/settings/contacts_page_settings.helpers.phones'))
                            ->schema([
                                Select::make('type')
                                    ->label(__('admin/settings/contacts_page_settings.labels.phone_type'))
                                    ->options([
                                        'mobile' => __('admin/settings/contacts_page_settings.phone_types.mobile'),
                                        'landline' => __('admin/settings/contacts_page_settings.phone_types.landline'),
                                    ])
                                    ->required(),
                                TextInput::make('value')
                                    ->label(__('admin/settings/contacts_page_settings.labels.phone'))
                                    ->required()
                                    ->maxLength(50),
                                TextInput::make('sort_order')
                                    ->label(__('admin/default.labels.sort_order'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(1)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel(__('admin/default.actions.add'))
                            ->reorderable()
                            ->rules(['array']),
                    ])
                    ->columnSpanFull(),

                Section::make(__('admin/settings/contacts_page_settings.sections.emails'))
                    ->schema([
                        Repeater::make('emails')
                            ->label(__('admin/settings/contacts_page_settings.sections.emails'))
                            ->helperText(__('admin/settings/contacts_page_settings.helpers.emails'))
                            ->schema([
                                TextInput::make('value')
                                    ->label(__('admin/settings/contacts_page_settings.labels.email'))
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('sort_order')
                                    ->label(__('admin/default.labels.sort_order'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(1)
                                    ->required(),
                            ])
                            ->columns()
                            ->defaultItems(0)
                            ->addActionLabel(__('admin/default.actions.add'))
                            ->reorderable()
                            ->rules(['array']),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    private static function createImagesTab(): Tabs\Tab
    {
        $max_upload_size_kb = max(1, (int) config('app.images.default_max_upload_image_size_kb', 5120));

        return Tabs\Tab::make(__('admin/settings/contacts_page_settings.tabs.images'))
            ->schema([
                Repeater::make('images')
                    ->label(__('admin/settings/contacts_page_settings.labels.images'))
                    ->helperText(__('admin/settings/contacts_page_settings.helpers.images'))
                    ->schema([
                        FileUpload::make('path')
                            ->label(__('admin/settings/contacts_page_settings.labels.image'))
                            ->helperText(__('admin/settings/contacts_page_settings.helpers.image'))
                            ->image()
                            ->directory('images/contacts/{year}/{month}')
                            ->maxSize($max_upload_size_kb)
                            ->rules([
                                'nullable',
                                Rule::file()::types(['image/jpeg', 'image/png', 'image/svg+xml']),
                                "max:$max_upload_size_kb",
                            ])
                            ->preserveFilenames()
                            ->imageEditor()
                            ->imageEditorViewportWidth(600)
                            ->imageEditorViewportHeight(600)
                            ->imageEditorAspectRatioOptions([
                                '1:1' => '1:1',
                                '4:3' => '4:3',
                                '16:9' => '16:9',
                            ])
                            ->visibility('public')
                            ->nullable(),

                        Grid::make()
                            ->schema([
                                TextInput::make('width')
                                    ->label(__('admin/settings/contacts_page_settings.labels.width'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(600)
                                    ->required(),
                                TextInput::make('height')
                                    ->label(__('admin/settings/contacts_page_settings.labels.height'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(600)
                                    ->required(),
                                Toggle::make('is_square')
                                    ->label(__('admin/settings/contacts_page_settings.labels.is_square'))
                                    ->default(true)
                                    ->required(),
                                TextInput::make('background')
                                    ->label(__('admin/settings/contacts_page_settings.labels.background'))
                                    ->helperText(__('admin/settings/contacts_page_settings.helpers.background'))
                                    ->placeholder('transparent or #FFFFFF')
                                    ->default('transparent')
                                    ->required()
                                    ->rules([
                                        'required',
                                        'string',
                                        'regex:/^(transparent|#[0-9a-fA-F]{6})$/',
                                    ]),
                                TextInput::make('custom_css_classes')
                                    ->label(__('admin/settings/contacts_page_settings.labels.custom_css_classes'))
                                    ->helperText(__('admin/settings/contacts_page_settings.helpers.custom_css_classes'))
                                    ->maxLength(1000)
                                    ->nullable(),
                                TextInput::make('sort_order')
                                    ->label(__('admin/default.labels.sort_order'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(1)
                                    ->required(),
                            ])
                            ->columns(),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel(__('admin/default.actions.add'))
                    ->reorderable()
                    ->rules(['array'])
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    private static function createContactFormTab(): Tabs\Tab
    {
        $field_tabs = [];

        foreach (['name', 'email', 'phone', 'text', 'file'] as $field_name) {
            $field_tabs[] = Section::make(__('admin/settings/contacts_page_settings.fields.' . $field_name))
                ->schema([
                    Toggle::make("contact_form.fields.$field_name.enabled")
                        ->label(__('admin/settings/contacts_page_settings.labels.field_enabled'))
                        ->default(true)
                        ->live(),
                    Toggle::make("contact_form.fields.$field_name.required")
                        ->label(__('admin/settings/contacts_page_settings.labels.field_required'))
                        ->default(false),
                    TextInput::make("contact_form.fields.$field_name.regex")
                        ->label(__('admin/settings/contacts_page_settings.labels.regex'))
                        ->helperText(__('admin/settings/contacts_page_settings.helpers.regex'))
                        ->maxLength(1000)
                        ->nullable()
                        ->rules([new ValidRegexMask()]),
                    TextInput::make("contact_form.fields.$field_name.min_length")
                        ->label(__('admin/settings/contacts_page_settings.labels.min_length'))
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                    TextInput::make("contact_form.fields.$field_name.max_length")
                        ->label(__('admin/settings/contacts_page_settings.labels.max_length'))
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                    ...($field_name === 'file' ? self::fileFieldSchema() : []),
                ])
                ->columns();
        }

        return Tabs\Tab::make(__('admin/settings/contacts_page_settings.tabs.contact_form'))
            ->schema($field_tabs)
            ->columns(1);
    }

    /**
     * @return array<int, mixed>
     */
    private static function fileFieldSchema(): array
    {
        $max_upload_size_kb = max(1, (int) config('app.images.default_max_upload_image_size_kb', 5120));

        return [
            TextInput::make('contact_form.fields.file.max_size_kb')
                ->label(__('admin/settings/contacts_page_settings.labels.max_file_size'))
                ->helperText(__('admin/settings/contacts_page_settings.helpers.max_file_size'))
                ->numeric()
                ->minValue(1)
                ->maxValue($max_upload_size_kb)
                ->default($max_upload_size_kb)
                ->required(),
            TagsInput::make('contact_form.fields.file.allowed_types')
                ->label(__('admin/settings/contacts_page_settings.labels.allowed_file_types'))
                ->helperText(__('admin/settings/contacts_page_settings.helpers.allowed_file_types'))
                ->default(['jpg', 'jpeg', 'png'])
                ->required(),
            TextInput::make('contact_form.fields.file.upload_path')
                ->label(__('admin/settings/contacts_page_settings.labels.upload_path'))
                ->helperText(__('admin/settings/contacts_page_settings.helpers.upload_path'))
                ->placeholder('images/contacts/{year}/{month}')
                ->nullable()
                ->maxLength(255),
        ];
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    private static function createEmailTab(Collection $active_languages): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/settings/contacts_page_settings.tabs.email'))
            ->schema([
                Section::make(__('admin/settings/contacts_page_settings.sections.email_destination'))
                    ->schema([
                        Checkbox::make('contact_form.destinations.email.enabled')
                            ->label(__('admin/settings/contacts_page_settings.labels.email_enabled'))
                            ->live(),
                        Toggle::make('contact_form.destinations.email.send_file')
                            ->label(__('admin/settings/contacts_page_settings.labels.send_file'))
                            ->helperText(__('admin/settings/contacts_page_settings.helpers.send_file')),
                        TextInput::make('contact_form.destinations.email.address')
                            ->label(__('admin/settings/contacts_page_settings.labels.email_address'))
                            ->email()
                            ->required(fn (Get $get): bool => (bool) $get('contact_form.destinations.email.enabled')),
                    ])
                    ->columns(),
                Section::make(__('admin/settings/contacts_page_settings.sections.email_templates'))
                    ->schema([
                        self::createEmailTemplateTabs($active_languages),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    private static function createTelegramTab(Collection $active_languages): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/settings/contacts_page_settings.tabs.telegram'))
            ->schema([
                Section::make(__('admin/settings/contacts_page_settings.sections.telegram_destination'))
                    ->schema([
                        Checkbox::make('contact_form.destinations.telegram.enabled')
                            ->label(__('admin/settings/contacts_page_settings.labels.telegram_enabled'))
                            ->live(),
                        TextInput::make('contact_form.destinations.telegram.bot_token')
                            ->label(__('admin/settings/contacts_page_settings.labels.telegram_bot_token'))
                            ->helperText(__('admin/settings/contacts_page_settings.helpers.telegram_bot_token'))
                            ->password()
                            ->revealable()
                            ->required(fn (Get $get): bool => (bool) $get('contact_form.destinations.telegram.enabled')),

                        Toggle::make('contact_form.destinations.telegram.send_file')
                            ->label(__('admin/settings/contacts_page_settings.labels.send_file'))
                            ->helperText(__('admin/settings/contacts_page_settings.helpers.send_file')),

                        TextInput::make('contact_form.destinations.telegram.chat_id')
                            ->label(__('admin/settings/contacts_page_settings.labels.telegram_chat_id'))
                            ->helperText(__('admin/settings/contacts_page_settings.helpers.telegram_chat_id'))
                            ->required(fn (Get $get): bool => (bool) $get('contact_form.destinations.telegram.enabled')),
                    ])
                    ->columns(),
                Section::make(__('admin/settings/contacts_page_settings.sections.telegram_templates'))
                    ->schema([
                        self::createTelegramTemplateTabs($active_languages),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    private static function createEmailTemplateTabs(Collection $active_languages): Tabs
    {
        $tabs = $active_languages->map(function (Language $language): Tabs\Tab {
            return Tabs\Tab::make($language->name)
                ->badge($language->code)
                ->schema([
                    TextInput::make("email.templates.$language->id.subject")
                        ->label(__('admin/settings/contacts_page_settings.labels.email_subject'))
                        ->nullable()
                        ->maxLength(255),
                    Textarea::make("email.templates.$language->id.body")
                        ->label(__('admin/settings/contacts_page_settings.labels.email_body'))
                        ->helperText(__('admin/settings/contacts_page_settings.helpers.template_placeholders'))
                        ->rows(8)
                        ->nullable(),
                ]);
        })->all();

        return Tabs::make('ContactsEmailTemplateTabs')
            ->tabs($tabs)
            ->activeTab(1)
            ->contained(false);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    private static function createTelegramTemplateTabs(Collection $active_languages): Tabs
    {
        $tabs = $active_languages->map(function (Language $language): Tabs\Tab {
            return Tabs\Tab::make($language->name)
                ->badge($language->code)
                ->schema([
                    Textarea::make("telegram.templates.$language->id.body")
                        ->label(__('admin/settings/contacts_page_settings.labels.telegram_body'))
                        ->helperText(__('admin/settings/contacts_page_settings.helpers.template_placeholders'))
                        ->rows(8)
                        ->nullable(),
                ]);
        })->all();

        return Tabs::make('ContactsTelegramTemplateTabs')
            ->tabs($tabs)
            ->activeTab(1)
            ->contained(false);
    }

    private static function createMapTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/settings/contacts_page_settings.tabs.map'))
            ->schema([
                Textarea::make('map.iframe')
                    ->label(__('admin/settings/contacts_page_settings.labels.map_iframe'))
                    ->helperText(__('admin/settings/contacts_page_settings.helpers.map_iframe'))
                    ->rows(5)
                    ->live()
                    ->nullable(),
                Grid::make()
                    ->schema([
                        Group::make([
                            TextInput::make('map.latitude')
                                ->label(__('admin/settings/contacts_page_settings.labels.map_latitude'))
                                ->helperText(__('admin/settings/contacts_page_settings.helpers.map_coordinates'))
                                ->numeric()
                                ->minValue(-90)
                                ->maxValue(90)
                                ->nullable(),
                            TextInput::make('map.longitude')
                                ->label(__('admin/settings/contacts_page_settings.labels.map_longitude'))
                                ->helperText(__('admin/settings/contacts_page_settings.helpers.map_coordinates'))
                                ->numeric()
                                ->minValue(-180)
                                ->maxValue(180)
                                ->nullable(),
                        ])
                        ->columns(),

                        Group::make([
                            TextInput::make('map.width')
                                ->label(__('admin/settings/contacts_page_settings.labels.width'))
                                ->numeric()
                                ->minValue(1)
                                ->default(600)
                                ->required(fn (Get $get): bool => filled($get('map.iframe'))),
                            TextInput::make('map.height')
                                ->label(__('admin/settings/contacts_page_settings.labels.height'))
                                ->numeric()
                                ->minValue(1)
                                ->default(400)
                                ->required(fn (Get $get): bool => filled($get('map.iframe'))),
                        ])
                            ->columns(),

                        TextInput::make('map.custom_css_classes')
                            ->label(__('admin/settings/contacts_page_settings.labels.custom_css_classes'))
                            ->helperText(__('admin/settings/contacts_page_settings.helpers.custom_css_classes'))
                            ->nullable()
                            ->maxLength(1000),
                    ])
                    ->columns(1),
            ]);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    private static function createAddressTab(Collection $active_languages): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/settings/contacts_page_settings.tabs.address'))
            ->schema([
                Repeater::make('addresses')
                    ->label(__('admin/settings/contacts_page_settings.labels.addresses'))
                    ->helperText(__('admin/settings/contacts_page_settings.helpers.addresses'))
                    ->schema([
                        Tabs::make('ContactsAddressLanguageTabs')
                            ->tabs(self::createAddressLanguageTabs($active_languages))
                            ->activeTab(1)
                            ->contained(false),
                        TextInput::make('sort_order')
                            ->label(__('admin/default.labels.sort_order'))
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->required(),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel(__('admin/default.actions.add'))
                    ->reorderable()
                    ->rules(['array'])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param Collection<int, Language> $active_languages
     * @return array<int, Tabs\Tab>
     */
    private static function createAddressLanguageTabs(Collection $active_languages): array
    {
        return $active_languages->map(function (Language $language): Tabs\Tab {
            $language_id = (string) $language->id;

            return Tabs\Tab::make($language->name)
                ->badge($language->code)
                ->schema([
                    TextInput::make("localized.$language_id.title")
                        ->label(__('admin/settings/contacts_page_settings.labels.address_title'))
                        ->required()
                        ->maxLength(255),
                    Textarea::make("localized.$language_id.description")
                        ->label(__('admin/settings/contacts_page_settings.labels.address_description'))
                        ->rows(3)
                        ->nullable(),
                    Textarea::make("localized.$language_id.value")
                        ->label(__('admin/settings/contacts_page_settings.labels.address'))
                        ->rows(3)
                        ->required(),
                    TextInput::make("localized.$language_id.url")
                        ->label(__('admin/settings/contacts_page_settings.labels.address_url'))
                        ->url()
                        ->nullable()
                        ->maxLength(2048),
                ])
                ->columns(1);
        })->all();
    }
}
