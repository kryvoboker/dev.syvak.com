<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\AppSettings\Schemas;

use App\Filament\Resources\Trait\LanguageTrait;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class AppSettingForm
{
    use LanguageTrait;

    /**
     * Configure application settings form schema
     */
    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getAcriveLanguages();

        return $schema
            ->components([
                Tabs::make('MainSettings')
                    ->tabs([
                        // SEO Tab with language tabs inside
                        Tabs\Tab::make(__('admin/settings/app_settings.tabs.seo'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        self::createLanguageTabs($active_languages, 'seo'),
                                    ]),
                            ])
                            ->columns(1),

                        Tabs\Tab::make(__('admin/settings/app_settings.tabs.contacts'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        self::createLanguageTabs($active_languages, 'contacts'),
                                    ]),
                            ])
                            ->columns(1),

                        Tabs\Tab::make(__('admin/settings/app_settings.tabs.user'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('user_settings.upload.max_size_mb')
                                            ->label(__('admin/settings/app_settings.labels.user_upload_max_size_mb'))
                                            ->helperText(__('admin/settings/app_settings.helpers.user_upload_max_size_mb'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),

                                        TextInput::make('user_settings.image_path')
                                            ->label(__('admin/settings/app_settings.labels.user_image_path'))
                                            ->helperText(__('admin/settings/app_settings.helpers.user_image_path'))
                                            ->placeholder('images/avatars/{year}/{month}')
                                            ->rules(['required', 'string', 'max:255'])
                                            ->required(),

                                        FileUpload::make('user_settings.no_image')
                                            ->label(__('admin/settings/app_settings.labels.user_no_image'))
                                            ->helperText(__('admin/settings/app_settings.helpers.user_no_image'))
                                            ->image()
                                            ->directory('images')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->required(),

                                        Grid::make()
                                            ->schema([
                                                TextInput::make('user_settings.preview_in_list_in_admin.width')
                                                    ->label(__('admin/settings/app_settings.labels.user_preview_list_width'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.user_preview_list_width'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('user_settings.preview_in_list_in_admin.height')
                                                    ->label(__('admin/settings/app_settings.labels.user_preview_list_height'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.user_preview_list_height'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('user_settings.preview_in_page_in_admin.width')
                                                    ->label(__('admin/settings/app_settings.labels.user_preview_page_width'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.user_preview_page_width'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('user_settings.preview_in_page_in_admin.height')
                                                    ->label(__('admin/settings/app_settings.labels.user_preview_page_height'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.user_preview_page_height'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                            ]),
                                    ]),
                            ])
                            ->columns(1),

                        // Map Settings Tab
                        Tabs\Tab::make(__('admin/settings/app_settings.tabs.map'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('coordinates')
                                            ->label(__('admin/settings/app_settings.labels.coordinates'))
                                            ->helperText(__('admin/settings/app_settings.helpers.coordinates'))
                                            ->placeholder(__('admin/settings/app_settings.placeholders.coordinates'))
                                            ->rules(['nullable', 'string', 'regex:' . config('app.regex_validate_conditions.coordinates'), 'max:255'])
                                            ->nullable()
                                            ->maxLength(255),

                                        Textarea::make('iframe_map')
                                            ->label(__('admin/settings/app_settings.labels.iframe_map'))
                                            ->helperText(__('admin/settings/app_settings.helpers.iframe_map'))
                                            ->rows(4)
                                            ->rules(['nullable', 'string'])
                                            ->nullable(),
                                    ]),
                            ])
                            ->columns(1),

                        // System Settings Tab
                        Tabs\Tab::make(__('admin/settings/app_settings.tabs.system'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Select::make('timezone')
                                            ->label(__('admin/settings/app_settings.labels.timezone'))
                                            ->helperText(__('admin/settings/app_settings.helpers.timezone'))
                                            ->options(function () {
                                                $timezones = [];

                                                foreach (timezone_identifiers_list() as $timezone) {
                                                    $offset = Carbon::now($timezone)->format('P'); // +02:00 format
                                                    $timezones[$timezone] = "$timezone ($offset)";
                                                }

                                                return $timezones;
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->rules(['required', 'string', 'in:' . implode(',', timezone_identifiers_list())])
                                            ->default(config('app.timezone'))
                                            ->required(),

                                        TextInput::make('system_settings.max_viewport_width')
                                            ->label(__('admin/settings/app_settings.labels.max_viewport_width'))
                                            ->helperText(__('admin/settings/app_settings.helpers.max_viewport_width'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),

                                        TextInput::make('system_settings.images.path_to_logo')
                                            ->label(__('admin/settings/app_settings.labels.path_to_logo'))
                                            ->helperText(__('admin/settings/app_settings.helpers.path_to_logo'))
                                            ->rules(['required', 'string', 'max:255'])
                                            ->required(),

                                        TextInput::make('system_settings.images.default_no_image')
                                            ->label(__('admin/settings/app_settings.labels.default_no_image'))
                                            ->helperText(__('admin/settings/app_settings.helpers.default_no_image'))
                                            ->rules(['required', 'string', 'max:255'])
                                            ->required(),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('system_settings.images.prototype_quality')
                                                    ->label(__('admin/settings/app_settings.labels.prototype_quality'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.prototype_quality'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->maxValue(100)
                                                    ->required(),

                                                TextInput::make('system_settings.images.webp_quality')
                                                    ->label(__('admin/settings/app_settings.labels.webp_quality'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.webp_quality'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->maxValue(100)
                                                    ->required(),

                                                TextInput::make('system_settings.images.avif_quality')
                                                    ->label(__('admin/settings/app_settings.labels.avif_quality'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.avif_quality'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->maxValue(100)
                                                    ->required(),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('system_settings.images.total_sizes_for_generate')
                                                    ->label(__('admin/settings/app_settings.labels.total_sizes_for_generate'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.total_sizes_for_generate'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('system_settings.images.max_image_width_for_convert')
                                                    ->label(__('admin/settings/app_settings.labels.max_image_width_for_convert'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.max_image_width_for_convert'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('system_settings.images.max_image_height_for_convert')
                                                    ->label(__('admin/settings/app_settings.labels.max_image_height_for_convert'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.max_image_height_for_convert'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                            ]),

                                        Repeater::make('image_sizes')
                                            ->label(__('admin/settings/app_settings.labels.image_sizes'))
                                            ->helperText(__('admin/settings/app_settings.helpers.image_sizes'))
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label(__('admin/default.labels.name'))
                                                    ->rules(['required', 'string', 'max:255'])
                                                    ->maxLength(255)
                                                    ->required(),

                                                TextInput::make('width')
                                                    ->label(__('admin/default.labels.width'))
                                                    ->numeric()
                                                    ->rules(['required', 'numeric'])
                                                    ->minValue(0)
                                                    ->default(0)
                                                    ->required(),

                                                TextInput::make('height')
                                                    ->label(__('admin/default.labels.height'))
                                                    ->numeric()
                                                    ->rules(['required', 'numeric'])
                                                    ->minValue(0)
                                                    ->default(0)
                                                    ->required(),
                                            ])
                                            ->columns(3),
                                    ]),
                            ])
                            ->columns(1),

                        // AI Settings Tab
                        Tabs\Tab::make(__('admin/settings/app_settings.tabs.ai'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('ai_settings.api_model')
                                            ->label(__('admin/settings/app_settings.labels.ai_api_model'))
                                            ->helperText(__('admin/settings/app_settings.helpers.ai_api_model'))
                                            ->rules(['required', 'string', 'max:255'])
                                            ->maxLength(255)
                                            ->required(),

                                        TextInput::make('ai_settings.api_temperature')
                                            ->label(__('admin/settings/app_settings.labels.ai_api_temperature'))
                                            ->helperText(__('admin/settings/app_settings.helpers.ai_api_temperature'))
                                            ->numeric()
                                            ->rules(['required', 'numeric', 'min:0', 'max:2'])
                                            ->minValue(0)
                                            ->maxValue(2)
                                            ->required(),

                                        TextInput::make('ai_settings.api_max_tokens')
                                            ->label(__('admin/settings/app_settings.labels.ai_api_max_tokens'))
                                            ->helperText(__('admin/settings/app_settings.helpers.ai_api_max_tokens'))
                                            ->numeric()
                                            ->rules(['required', 'numeric', 'min:1'])
                                            ->minValue(1)
                                            ->required(),

                                        Textarea::make('ai_settings.system_prompt')
                                            ->label(__('admin/settings/app_settings.labels.ai_system_prompt'))
                                            ->helperText(__('admin/settings/app_settings.helpers.ai_system_prompt'))
                                            ->rows(4)
                                            ->rules(['required', 'string'])
                                            ->required(),

                                        Grid::make()
                                            ->schema([
                                                TextInput::make('ai_settings.api_wait_time_seconds')
                                                    ->label(__('admin/settings/app_settings.labels.ai_api_wait_time_seconds'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.ai_api_wait_time_seconds'))
                                                    ->numeric()
                                                    ->rules(['required', 'numeric', 'min:0'])
                                                    ->minValue(0)
                                                    ->required(),

                                                TextInput::make('ai_settings.api_max_calls')
                                                    ->label(__('admin/settings/app_settings.labels.ai_api_max_calls'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.ai_api_max_calls'))
                                                    ->numeric()
                                                    ->rules(['required', 'numeric', 'min:1'])
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('ai_settings.api_max_retries')
                                                    ->label(__('admin/settings/app_settings.labels.ai_api_max_retries'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.ai_api_max_retries'))
                                                    ->numeric()
                                                    ->rules(['required', 'numeric', 'min:0'])
                                                    ->minValue(0)
                                                    ->required(),

                                                TextInput::make('ai_settings.api_max_retry_wait_time_seconds')
                                                    ->label(__('admin/settings/app_settings.labels.ai_api_max_retry_wait_time_seconds'))
                                                    ->helperText(__('admin/settings/app_settings.helpers.ai_api_max_retry_wait_time_seconds'))
                                                    ->numeric()
                                                    ->rules(['required', 'numeric', 'min:0'])
                                                    ->minValue(0)
                                                    ->required(),
                                            ]),
                                    ]),
                            ])
                            ->columns(1),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Create language tabs for specific section
     */
    protected static function createLanguageTabs(Collection $languages, string $section): Tabs
    {
        $tabs = [];

        foreach ($languages as $language) {
            $tabs[] = Tabs\Tab::make($language->name)
                ->schema(self::getSchemaForSection($section, $language->code))
                ->badge($language->code);
        }

        return Tabs::make('LanguageTabs')
            ->tabs($tabs)
            ->activeTab(1)
            ->contained(false);
    }

    /**
     * Get schema for specific section and language
     */
    protected static function getSchemaForSection(string $section, string $lang_code): array
    {
        return match ($section) {
            'seo' => [
                TextInput::make("titles.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.titles'))
                    ->helperText(__('admin/settings/app_settings.helpers.titles'))
                    ->rules(['required', 'string', 'max:255'])
                    ->maxLength(255)
                    ->required(),

                TextInput::make("meta_titles.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.meta_titles'))
                    ->helperText(__('admin/settings/app_settings.helpers.meta_titles'))
                    ->rules(['nullable', 'max:255'])
                    ->maxLength(255)
                    ->nullable(),

                Textarea::make("meta_descriptions.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.meta_descriptions'))
                    ->helperText(__('admin/settings/app_settings.helpers.meta_descriptions'))
                    ->rows(3)
                    ->rules(['nullable', 'max:255'])
                    ->maxLength(255)
                    ->nullable(),

                TextInput::make("meta_keywords.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.meta_keywords'))
                    ->helperText(__('admin/settings/app_settings.helpers.meta_keywords'))
                    ->rules(['nullable', 'max:255'])
                    ->maxLength(255)
                    ->nullable(),
            ],
            'contacts' => [
                TextInput::make("contact_emails.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.contact_emails'))
                    ->helperText(__('admin/settings/app_settings.helpers.contact_emails'))
                    ->placeholder(__('admin/settings/app_settings.placeholders.contact_emails'))
                    ->rules(['nullable', 'string', 'max:500'])
                    ->maxLength(500)
                    ->nullable(),

                TextInput::make("contact_phones.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.contact_phones'))
                    ->helperText(__('admin/settings/app_settings.helpers.contact_phones'))
                    ->placeholder(__('admin/settings/app_settings.placeholders.contact_phones'))
                    ->rules(['nullable', 'string', 'max:500'])
                    ->maxLength(500)
                    ->nullable(),

                Repeater::make("socials.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.socials'))
                    ->helperText(__('admin/settings/app_settings.helpers.socials'))
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('social_type')
                                    ->label(__('admin/settings/app_settings.labels.social_type'))
                                    ->options([
                                        'facebook' => __('admin/default.texts.facebook'),
                                        'instagram' => __('admin/default.texts.instagram'),
                                        'twitter' => __('admin/default.texts.twitter'),
                                        'linkedin' => __('admin/default.texts.linkedin'),
                                        'youtube' => __('admin/default.texts.youtube'),
                                        'telegram' => __('admin/default.texts.telegram'),
                                        'tiktok' => __('admin/default.texts.tiktok'),
                                    ])
                                    ->searchable()
                                    ->rules(['required', 'string', 'in:' . implode(',', config('app.socials_list'))])
                                    ->required(),

                                TextInput::make('url')
                                    ->label(__('admin/default.labels.url'))
                                    ->placeholder('https://example.com')
                                    ->url()
                                    ->rules(['required', 'url', 'max:255'])
                                    ->maxLength(255)
                                    ->required(),

                                Textarea::make('svg_icon')
                                    ->label(__('admin/default.labels.svg_icon'))
                                    ->helperText(__('admin/default.helpers.svg_icon'))
                                    ->rows(4)
                                    ->rules(['nullable', 'string'])
                                    ->columnSpan(2)
                                    ->nullable(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(),

                TextInput::make("work_time.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.work_time'))
                    ->helperText(__('admin/settings/app_settings.helpers.work_time'))
                    ->placeholder(__('admin/settings/app_settings.placeholders.work_time'))
                    ->rules(['nullable', 'string', 'max:255'])
                    ->maxLength(255)
                    ->nullable(),

                Textarea::make("contact_addresses.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.contact_addresses'))
                    ->helperText(__('admin/settings/app_settings.helpers.contact_addresses'))
                    ->maxLength(1000)
                    ->rows(2)
                    ->rules(['nullable', 'string', 'max:1000'])
                    ->nullable(),
            ],
            default => [],
        };
    }
}
