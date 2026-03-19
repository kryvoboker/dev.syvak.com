<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\AppSettings\Schemas;

use App\Filament\Resources\Trait\LanguageTrait;
use Carbon\Carbon;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
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
                                self::createLanguageTabs($active_languages, 'seo'),
                            ])
                            ->columns(1),

                        // Contact Information Tab with language tabs inside
                        Tabs\Tab::make(__('admin/settings/app_settings.tabs.contacts'))
                            ->schema([
                                self::createLanguageTabs($active_languages, 'contacts'),
                            ])
                            ->columns(1),

                        // Map Settings Tab
                        Tabs\Tab::make(__('admin/settings/app_settings.tabs.map'))
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
                            ])
                            ->columns(1),

                        // System Settings Tab
                        Tabs\Tab::make(__('admin/settings/app_settings.tabs.system'))
                            ->schema([
                                Select::make('timezone')
                                    ->label(__('admin/settings/app_settings.labels.timezone'))
                                    ->helperText(__('admin/settings/app_settings.helpers.timezone'))
                                    ->options(function () {
                                        $timezones = [];

                                        foreach (timezone_identifiers_list() as $timezone) {
                                            $offset               = Carbon::now($timezone)->format('P'); // +02:00 format
                                            $timezones[$timezone] = "$timezone ($offset)";
                                        }

                                        return $timezones;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->rules(['required', 'string', 'in:' . implode(',', timezone_identifiers_list())])
                                    ->default(config('app.timezone'))
                                    ->required(),

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
                            ])
                            ->columns(1),
                    ])
                    ->activeTab(1)
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
                    ->rules(['nullable', 'string', 'max:255'])
                    ->maxLength(255)
                    ->nullable(),

                Textarea::make("meta_descriptions.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.meta_descriptions'))
                    ->helperText(__('admin/settings/app_settings.helpers.meta_descriptions'))
                    ->rows(3)
                    ->rules(['nullable', 'string', 'max:255'])
                    ->maxLength(255)
                    ->nullable(),

                TextInput::make("meta_keywords.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.meta_keywords'))
                    ->helperText(__('admin/settings/app_settings.helpers.meta_keywords'))
                    ->rules(['nullable', 'string', 'max:255'])
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
                                        'facebook'  => __('admin/default.texts.facebook'),
                                        'instagram' => __('admin/default.texts.instagram'),
                                        'twitter'   => __('admin/default.texts.twitter'),
                                        'linkedin'  => __('admin/default.texts.linkedin'),
                                        'youtube'   => __('admin/default.texts.youtube'),
                                        'telegram'  => __('admin/default.texts.telegram'),
                                        'tiktok'    => __('admin/default.texts.tiktok'),
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
