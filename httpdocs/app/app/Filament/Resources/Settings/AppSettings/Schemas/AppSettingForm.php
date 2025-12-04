<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\AppSettings\Schemas;

use App\Models\Settings\Language;
use Carbon\Carbon;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class AppSettingForm
{
    /**
     * Configure application settings form schema
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        $languages = new Language()->getActiveLanguages();

        return $schema
            ->components([
                Tabs::make('MainSettings')
                    ->tabs([
                        // SEO Tab with language tabs inside
                        Tabs\Tab::make(__('admin/settings/app_settings.tab_seo'))
                            ->schema([
                                self::createLanguageTabs($languages, 'seo'),
                            ])
                            ->columns(1),

                        // Contact Information Tab with language tabs inside
                        Tabs\Tab::make(__('admin/settings/app_settings.tab_contacts'))
                            ->schema([
                                self::createLanguageTabs($languages, 'contacts'),
                            ])
                            ->columns(1),

                        // Map Settings Tab
                        Tabs\Tab::make(__('admin/settings/app_settings.tab_map'))
                            ->schema([
                                TextInput::make('coordinates')
                                    ->label(__('admin/settings/app_settings.label_coordinates'))
                                    ->helperText(__('admin/settings/app_settings.helper_coordinates'))
                                    ->placeholder(__('admin/settings/app_settings.placeholder_coordinates'))
                                    ->maxLength(255)
                                    ->rules(['nullable', 'string', 'regex:/^-?\d{1,2}\.\d+,\s?-?\d{1,3}\.\d+$/', 'max:255']),

                                Textarea::make('iframe_map')
                                    ->label(__('admin/settings/app_settings.label_iframe_map'))
                                    ->helperText(__('admin/settings/app_settings.helper_iframe_map'))
                                    ->rules(['nullable', 'string'])
                                    ->rows(4),
                            ])
                            ->columns(1),

                        // System Settings Tab
                        Tabs\Tab::make(__('admin/settings/app_settings.tab_system'))
                            ->schema([
                                Select::make('timezone')
                                    ->label(__('admin/settings/app_settings.label_timezone'))
                                    ->helperText(__('admin/settings/app_settings.helper_timezone'))
                                    ->options(function () {
                                        $timezones = [];

                                        foreach (timezone_identifiers_list() as $timezone) {
                                            $offset = Carbon::now($timezone)->format('P'); // +02:00 format
                                            $timezones[$timezone] = "$timezone ($offset)";
                                        }

                                        return $timezones;
                                    })
                                    ->searchable()
                                    ->rules(['required', 'string', 'in:' . implode(',', timezone_identifiers_list())])
                                    ->default(config('app.timezone'))
                                    ->required(),

                                Repeater::make('image_sizes')
                                    ->label(__('admin/settings/app_settings.label_image_sizes'))
                                    ->helperText(__('admin/settings/app_settings.helper_image_sizes'))
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('admin/settings/app_settings.label_name'))
                                            ->rules(['required', 'string', 'max:255']),
                                        TextInput::make('width')
                                            ->label(__('admin/settings/app_settings.label_width'))
                                            ->numeric()
                                            ->rules(['required', 'numeric']),
                                        TextInput::make('height')
                                            ->label(__('admin/settings/app_settings.label_height'))
                                            ->numeric()
                                            ->rules(['required', 'numeric']),
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
     *
     * @param Collection $languages
     * @param string     $section
     *
     * @return Tabs
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
     *
     * @param string $section
     * @param string $lang_code
     *
     * @return array
     */
    protected static function getSchemaForSection(string $section, string $lang_code): array
    {
        return match ($section) {
            'seo'      => [
                TextInput::make("titles.$lang_code")
                    ->label(__('admin/settings/app_settings.label_titles'))
                    ->helperText(__('admin/settings/app_settings.helper_titles'))
                    ->rules(['required', 'string', 'max:255'])
                    ->required(),

                TextInput::make("meta_titles.$lang_code")
                    ->label(__('admin/settings/app_settings.label_meta_titles'))
                    ->rules(['nullable', 'max:255'])
                    ->helperText(__('admin/settings/app_settings.helper_meta_titles'))
                ->default(null),

                Textarea::make("meta_descriptions.$lang_code")
                    ->label(__('admin/settings/app_settings.label_meta_descriptions'))
                    ->rules(['nullable', 'max:255'])
                    ->helperText(__('admin/settings/app_settings.helper_meta_descriptions'))
                    ->rows(3),

                TextInput::make("meta_keywords.$lang_code")
                    ->label(__('admin/settings/app_settings.label_meta_keywords'))
                    ->rules(['nullable', 'max:255'])
                    ->helperText(__('admin/settings/app_settings.helper_meta_keywords')),
            ],
            'contacts' => [
                TextInput::make("contact_emails.$lang_code")
                    ->label(__('admin/settings/app_settings.label_contact_emails'))
                    ->helperText(__('admin/settings/app_settings.helper_contact_emails'))
                    ->placeholder(__('admin/settings/app_settings.placeholder_contact_emails'))
                    ->rules(['nullable', 'string', 'max:500'])
                    ->maxLength(500),

                TextInput::make("contact_phones.$lang_code")
                    ->label(__('admin/settings/app_settings.label_contact_phones'))
                    ->helperText(__('admin/settings/app_settings.helper_contact_phones'))
                    ->placeholder(__('admin/settings/app_settings.placeholder_contact_phones'))
                    ->rules(['nullable', 'string', 'max:500'])
                    ->maxLength(500),

                Repeater::make("socials.$lang_code")
                    ->label(__('admin/settings/app_settings.label_socials'))
                    ->helperText(__('admin/settings/app_settings.helper_socials'))
                    ->schema([
                        Select::make('platform')
                            ->label(__('admin/settings/app_settings.label_platform'))
                            ->options([
                                'facebook'  => __('admin/settings/app_settings.text_facebook'),
                                'instagram' => __('admin/settings/app_settings.text_instagram'),
                                'twitter'   => __('admin/settings/app_settings.text_twitter'),
                                'linkedin'  => __('admin/settings/app_settings.text_linkedin'),
                                'youtube'   => __('admin/settings/app_settings.text_youtube'),
                                'telegram'  => __('admin/settings/app_settings.text_telegram'),
                            ])
                            ->rules(['required', 'string', 'in:' . implode(',', config('app.socials_list'))]),
                        TextInput::make('url')
                            ->label(__('admin/settings/app_settings.label_url'))
                            ->url(),
                    ])
                    ->columns(),

                TextInput::make("work_time.$lang_code")
                    ->label(__('admin/settings/app_settings.label_work_time'))
                    ->helperText(__('admin/settings/app_settings.helper_work_time'))
                    ->rules(['nullable', 'string', 'max:255'])
                    ->maxLength(255)
                    ->placeholder(__('admin/settings/app_settings.placeholder_work_time')),

                Textarea::make("contact_addresses.$lang_code")
                    ->label(__('admin/settings/app_settings.label_contact_addresses'))
                    ->helperText(__('admin/settings/app_settings.helper_contact_addresses'))
                    ->rows(2)
                    ->rules(['nullable', 'string', 'max:1000'])
                    ->maxLength(1000),
            ],
            default    => [],
        };
    }
}
