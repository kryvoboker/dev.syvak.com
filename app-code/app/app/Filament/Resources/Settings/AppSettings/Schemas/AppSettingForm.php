<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\AppSettings\Schemas;

use App\Filament\Resources\Trait\Forms\CommonTextFormTrait;
use App\Filament\Resources\Trait\Forms\NumericFormTrait;
use App\Filament\Resources\Trait\Forms\SelectFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use Carbon\Carbon;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class AppSettingForm
{
    use LanguageTrait, CommonTextFormTrait, NumericFormTrait, SelectFormTrait;

    /**
     * Configure application settings form schema
     *
     * @param Schema $schema
     *
     * @return Schema
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
                                self::getTextFormField([
                                    'field_name'  => 'coordinates',
                                    'label'       => __('admin/settings/app_settings.labels.coordinates'),
                                    'helper_text' => __('admin/settings/app_settings.helpers.coordinates'),
                                    'placeholder' => __('admin/settings/app_settings.placeholders.coordinates'),
                                    'rules'       => ['nullable', 'string', 'regex:' . config('app.regex_validate_conditions.coordinates'), 'max:255'],
                                    'required'    => false,
                                ]),

                                self::getTextAreaFormField([
                                    'field_name'  => 'iframe_map',
                                    'label'       => __('admin/settings/app_settings.labels.iframe_map'),
                                    'helper_text' => __('admin/settings/app_settings.helpers.iframe_map'),
                                    'rows'        => 4,
                                    'rules'       => ['nullable', 'string'],
                                ]),
                            ])
                            ->columns(1),

                        // System Settings Tab
                        Tabs\Tab::make(__('admin/settings/app_settings.tabs.system'))
                            ->schema([
                                self::getSelectFormField([
                                    'field_name'  => 'timezone',
                                    'label'       => __('admin/settings/app_settings.labels.timezone'),
                                    'helper_text' => __('admin/settings/app_settings.helpers.timezone'),
                                    'options'     => function () {
                                        $timezones = [];

                                        foreach (timezone_identifiers_list() as $timezone) {
                                            $offset               = Carbon::now($timezone)->format('P'); // +02:00 format
                                            $timezones[$timezone] = "$timezone ($offset)";
                                        }

                                        return $timezones;
                                    },
                                    'rules'       => ['string', 'in:' . implode(',', timezone_identifiers_list())],
                                    'default'     => config('app.timezone'),
                                    'required'    => true,
                                ]),

                                Repeater::make('image_sizes')
                                    ->label(__('admin/settings/app_settings.labels.image_sizes'))
                                    ->helperText(__('admin/settings/app_settings.helpers.image_sizes'))
                                    ->schema([
                                        self::getTextFormField([
                                            'field_name' => 'name',
                                            'label'      => __('admin/default.labels.name'),
                                            'rules'      => ['required', 'string', 'max:255'],
                                        ]),

                                        self::getNumericFormField([
                                            'field_name' => 'width',
                                            'label'      => __('admin/default.labels.width'),
                                        ]),

                                        self::getNumericFormField([
                                            'field_name' => 'height',
                                            'label'      => __('admin/default.labels.height'),
                                        ]),
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
                self::getTextFormField([
                    'field_name'  => "titles.$lang_code",
                    'label'       => __('admin/settings/app_settings.labels.titles'),
                    'helper_text' => __('admin/settings/app_settings.helpers.titles'),
                ]),

                self::getTextFormField([
                    'field_name'  => "meta_titles.$lang_code",
                    'label'       => __('admin/settings/app_settings.labels.meta_titles'),
                    'helper_text' => __('admin/settings/app_settings.helpers.meta_titles'),
                    'required'    => false,
                ]),

                self::getTextAreaFormField([
                    'field_name'  => "meta_descriptions.$lang_code",
                    'label'       => __('admin/settings/app_settings.labels.meta_descriptions'),
                    'helper_text' => __('admin/settings/app_settings.helpers.meta_descriptions'),
                    'rows'        => 3,
                ]),

                self::getTextFormField([
                    'field_name'  => "meta_keywords.$lang_code",
                    'label'       => __('admin/settings/app_settings.labels.meta_keywords'),
                    'helper_text' => __('admin/settings/app_settings.helpers.meta_keywords'),
                    'required'    => false,
                ]),
            ],
            'contacts' => [
                self::getTextFormField([
                    'field_name'  => "contact_emails.$lang_code",
                    'label'       => __('admin/settings/app_settings.labels.contact_emails'),
                    'helper_text' => __('admin/settings/app_settings.helpers.contact_emails'),
                    'placeholder' => __('admin/settings/app_settings.placeholders.contact_emails'),
                    'max_length'  => 500,
                    'required'    => false,
                ]),

                self::getTextFormField([
                    'field_name'  => "contact_phones.$lang_code",
                    'label'       => __('admin/settings/app_settings.labels.contact_phones'),
                    'helper_text' => __('admin/settings/app_settings.helpers.contact_phones'),
                    'placeholder' => __('admin/settings/app_settings.placeholders.contact_phones'),
                    'max_length'  => 500,
                    'required'    => false,
                ]),

                Repeater::make("socials.$lang_code")
                    ->label(__('admin/settings/app_settings.labels.socials'))
                    ->helperText(__('admin/settings/app_settings.helpers.socials'))
                    ->schema([
                        Grid::make()
                            ->schema([
                                self::getSelectFormField([
                                    'field_name' => 'social_type',
                                    'label'      => __('admin/settings/app_settings.labels.social_type'),
                                    'options'    => [
                                        'facebook'  => __('admin/default.texts.facebook'),
                                        'instagram' => __('admin/default.texts.instagram'),
                                        'twitter'   => __('admin/default.texts.twitter'),
                                        'linkedin'  => __('admin/default.texts.linkedin'),
                                        'youtube'   => __('admin/default.texts.youtube'),
                                        'telegram'  => __('admin/default.texts.telegram'),
                                        'tiktok'    => __('admin/default.texts.tiktok'),
                                    ],
                                    'rules'      => ['string', 'in:' . implode(',', config('app.socials_list'))],
                                    'required'   => true,
                                ]),

                                self::getUrlFormField([
                                    'field_name' => 'url',
                                    'label'      => __('admin/default.labels.url'),
                                    'required'   => true,
                                ]),

                                self::getTextAreaFormField([
                                    'field_name'  => 'svg_icon',
                                    'label'       => __('admin/default.labels.svg_icon'),
                                    'helper_text' => __('admin/default.helpers.svg_icon'),
                                    'column_span' => 2,
                                ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(),

                self::getTextFormField([
                    'field_name'  => "work_time.$lang_code",
                    'label'       => __('admin/settings/app_settings.labels.work_time'),
                    'helper_text' => __('admin/settings/app_settings.helpers.work_time'),
                    'placeholder' => __('admin/settings/app_settings.placeholders.work_time'),
                    'required'    => false,
                ]),

                self::getTextAreaFormField([
                    'field_name'  => "contact_addresses.$lang_code",
                    'label'       => __('admin/settings/app_settings.labels.contact_addresses'),
                    'helper_text' => __('admin/settings/app_settings.helpers.contact_addresses'),
                    'max_length'  => 1000,
                    'rows'        => 2,
                ]),
            ],
            default    => [],
        };
    }
}
