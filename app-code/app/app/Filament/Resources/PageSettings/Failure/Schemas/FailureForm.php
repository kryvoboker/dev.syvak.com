<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Failure\Schemas;

use App\Filament\Resources\Trait\Forms\SlugFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\ApplicationSettings\Language;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

class FailureForm
{
    use LanguageTrait;
    use SlugFormTrait;

    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getActiveLanguages();

        return $schema
            ->components([
                Tabs::make('FailurePageSettingsTabs')
                    ->tabs([
                        self::createGeneralSettingsTab($active_languages),
                        self::createImagesTab(),
                        self::createButtonsAndPaymentsTab($active_languages),
                        self::createSupportContactsTab($active_languages),
                        self::createSlugsFormTabs($active_languages, __('admin/settings/failure_page_settings.tabs.seo_url')),
                    ])
                    ->activeTab(5)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    protected static function createImagesTab(): Tabs\Tab
    {
        $max_upload_size_kb = max(1, (int) config('app.page_settings.failure.for_admin.upload_max_size_kb', 5120));

        return Tabs\Tab::make(__('admin/settings/failure_page_settings.tabs.images'))
            ->schema([
                Repeater::make('images')
                    ->label(__('admin/settings/failure_page_settings.labels.images'))
                    ->schema([
                        FileUpload::make('path')
                            ->label(__('admin/settings/failure_page_settings.labels.image'))
                            ->helperText(__('admin/settings/failure_page_settings.helpers.image'))
                            ->image()
                            ->directory('images/failure')
                            ->maxSize($max_upload_size_kb)
                            ->rules([
                                'required',
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
                            ->required(),
                        Grid::make()
                            ->schema([
                                TextInput::make('width')
                                    ->label(__('admin/settings/failure_page_settings.labels.width'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                                TextInput::make('height')
                                    ->label(__('admin/settings/failure_page_settings.labels.height'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                                Toggle::make('is_square')
                                    ->label(__('admin/settings/failure_page_settings.labels.is_square'))
                                    ->default(true)
                                    ->required(),
                                TextInput::make('background')
                                    ->label(__('admin/settings/failure_page_settings.labels.background'))
                                    ->helperText(__('admin/settings/failure_page_settings.helpers.background'))
                                    ->default('transparent')
                                    ->required()
                                    ->rules(['required', 'string', 'regex:/^(transparent|#[0-9a-fA-F]{6})$/']),
                                TextInput::make('custom_css_classes')
                                    ->label(__('admin/settings/failure_page_settings.labels.custom_css_classes'))
                                    ->helperText(__('admin/settings/failure_page_settings.helpers.custom_css_classes'))
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
            ]);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    protected static function createButtonsAndPaymentsTab(Collection $active_languages): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/settings/failure_page_settings.tabs.buttons_and_payments'))
            ->schema([
                Section::make(__('admin/settings/failure_page_settings.sections.retry_button'))
                    ->schema([
                        Toggle::make('buttons.retry.enabled')
                            ->label(__('admin/settings/failure_page_settings.labels.enabled'))
                            ->live(),
                        TextInput::make('buttons.retry.custom_css_classes')
                            ->label(__('admin/settings/failure_page_settings.labels.custom_css_classes'))
                            ->helperText(__('admin/settings/failure_page_settings.helpers.custom_css_classes'))
                            ->nullable()
                            ->maxLength(1000),
                        self::createLocalizedButtonTabs($active_languages, 'retry_button'),
                    ])
                    ->columns(1),
                Section::make(__('admin/settings/failure_page_settings.sections.alternative_payment_button'))
                    ->schema([
                        Toggle::make('buttons.alternative_payment.enabled')
                            ->label(__('admin/settings/failure_page_settings.labels.enabled'))
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                if ($state) {
                                    $set('buttons.available_payment_methods.enabled', true);
                                }
                            }),
                        TextInput::make('buttons.alternative_payment.custom_css_classes')
                            ->label(__('admin/settings/failure_page_settings.labels.custom_css_classes'))
                            ->helperText(__('admin/settings/failure_page_settings.helpers.custom_css_classes'))
                            ->nullable()
                            ->maxLength(1000),
                        self::createLocalizedButtonTabs($active_languages, 'alternative_payment_button'),
                    ])
                    ->columns(1),
                Section::make(__('admin/settings/failure_page_settings.sections.available_payment_methods'))
                    ->schema([
                        Toggle::make('buttons.available_payment_methods.enabled')
                            ->label(__('admin/settings/failure_page_settings.labels.enabled'))
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                if (! $state) {
                                    $set('buttons.alternative_payment.enabled', false);
                                }
                            }),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    protected static function createLocalizedButtonTabs(Collection $active_languages, string $button_key): Tabs
    {
        $tabs = $active_languages->map(function (Language $language) use ($button_key): Tabs\Tab {
            return Tabs\Tab::make($language->name)
                ->badge((string) $language->code)
                ->schema([
                    TextInput::make("localized_content.$language->id.$button_key.label")
                        ->label(__('admin/settings/failure_page_settings.labels.button_text'))
                        ->required(function (Get $get) use ($button_key): bool {
                            $setting = $button_key === 'retry_button'
                                ? 'buttons.retry.enabled'
                                : 'buttons.alternative_payment.enabled';

                            return (bool) $get($setting);
                        })
                        ->maxLength(255),
                ]);
        })->all();

        return Tabs::make('FailureLocalizedButtonTabs' . $button_key)
            ->tabs($tabs)
            ->activeTab(1)
            ->contained(false);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    protected static function createSupportContactsTab(Collection $active_languages): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/settings/failure_page_settings.tabs.support_contacts'))
            ->schema([
                Section::make(__('admin/settings/failure_page_settings.sections.working_hours'))
                    ->schema([
                        Toggle::make('support_contacts.use_contacts_working_hours')
                            ->label(__('admin/settings/failure_page_settings.labels.use_contacts_data'))
                            ->helperText(__('admin/settings/failure_page_settings.helpers.use_contacts_working_hours'))
                            ->default(true)
                            ->live(),
                        self::createLocalizedWorkingHoursTabs($active_languages)
                            ->visible(fn (Get $get): bool => !$get('support_contacts.use_contacts_working_hours')),
                    ]),
                Section::make(__('admin/settings/failure_page_settings.sections.phones'))
                    ->schema([
                        Toggle::make('support_contacts.use_contacts_phones')
                            ->label(__('admin/settings/failure_page_settings.labels.use_contacts_data'))
                            ->default(true)
                            ->live(),
                        self::createContactRepeater('support_contacts.phones', true)
                            ->visible(fn (Get $get): bool => !$get('support_contacts.use_contacts_phones')),
                    ]),
                Section::make(__('admin/settings/failure_page_settings.sections.emails'))
                    ->schema([
                        Toggle::make('support_contacts.use_contacts_emails')
                            ->label(__('admin/settings/failure_page_settings.labels.use_contacts_data'))
                            ->default(true)
                            ->live(),
                        self::createContactRepeater('support_contacts.emails')
                            ->visible(fn (Get $get): bool => !$get('support_contacts.use_contacts_emails')),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    protected static function createLocalizedWorkingHoursTabs(Collection $active_languages): Tabs
    {
        $tabs = $active_languages->map(function (Language $language): Tabs\Tab {
            return Tabs\Tab::make($language->name)
                ->badge((string) $language->code)
                ->schema([
                    Textarea::make("support_contacts.working_hours.$language->id.content")
                        ->label(__('admin/settings/failure_page_settings.labels.working_hours'))
                        ->rows(5)
                        ->nullable(),
                ]);
        })->all();

        return Tabs::make('FailureWorkingHoursLanguageTabs')
            ->tabs($tabs)
            ->activeTab(1)
            ->contained(false);
    }

    protected static function createContactRepeater(string $name, bool $is_phone = false): Repeater
    {
        $schema = [
            TextInput::make('value')
            ->label($is_phone
                ? __('admin/settings/failure_page_settings.labels.phone')
                : __('admin/settings/failure_page_settings.labels.email'))
            ->email(! $is_phone)
            ->required()
                ->maxLength(255),
            TextInput::make('sort_order')
                ->label(__('admin/default.labels.sort_order'))
                ->numeric()
                ->minValue(0)
                ->default(1)
                ->required(),
            TextInput::make('custom_css_classes')
                ->label(__('admin/settings/failure_page_settings.labels.custom_css_classes'))
                ->helperText(__('admin/settings/failure_page_settings.helpers.custom_css_classes'))
                ->nullable()
                ->maxLength(1000),
        ];

        if ($is_phone) {
            array_splice($schema, 1, 0, [
                Select::make('type')
                    ->label(__('admin/settings/failure_page_settings.labels.phone_type'))
                    ->options([
                        'mobile' => __('admin/settings/failure_page_settings.options.mobile'),
                        'landline' => __('admin/settings/failure_page_settings.options.landline'),
                    ])
                    ->default('mobile')
                    ->required(),
            ]);
        }

        return Repeater::make($name)
            ->label($is_phone
                ? __('admin/settings/failure_page_settings.labels.phones')
                : __('admin/settings/failure_page_settings.labels.emails'))
            ->schema($schema)
            ->defaultItems(0)
            ->addActionLabel(__('admin/default.actions.add'))
            ->reorderable()
            ->rules(['array']);
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    protected static function createGeneralSettingsTab(Collection $active_languages): Tabs\Tab
    {
        $language_tabs = $active_languages->map(function (Language $language): Tabs\Tab {
            return Tabs\Tab::make($language->name)
                ->badge((string) $language->code)
                ->schema([
                    TextInput::make("localized_content.$language->id.title")
                        ->label(__('admin/settings/failure_page_settings.labels.title'))
                        ->required()
                        ->maxLength(255),
                    Textarea::make("localized_content.$language->id.description")
                        ->label(__('admin/settings/failure_page_settings.labels.description'))
                        ->rows(4)
                        ->nullable(),
                ]);
        })->all();

        return Tabs\Tab::make(__('admin/settings/failure_page_settings.tabs.general_settings'))
            ->schema([
                Section::make(__('admin/settings/failure_page_settings.sections.localized_content'))
                    ->schema([
                        Tabs::make('FailureLocalizedContentTabs')
                            ->tabs($language_tabs)
                            ->activeTab(1)
                            ->contained(false),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
