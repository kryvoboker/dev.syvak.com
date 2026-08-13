<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\NotFound\Schemas;

use App\Filament\Resources\Trait\Forms\SlugFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\ApplicationSettings\Language;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

class NotFoundForm
{
    use LanguageTrait;
    use SlugFormTrait;

    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getActiveLanguages();

        return $schema
            ->components([
                Tabs::make('NotFoundPageSettingsTabs')
                    ->tabs([
                        self::createGeneralTab($active_languages),
                        self::createImagesTab(),
                        self::createSlugsFormTabs(
                            $active_languages,
                            __('admin/settings/not_found_page_settings.tabs.slugs'),
                        ),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  Collection<int, Language>  $active_languages
     */
    protected static function createGeneralTab(Collection $active_languages): Tabs\Tab
    {
        $language_tabs = [];

        foreach ($active_languages as $language) {
            $language_id = (string) $language->id;

            $language_tabs[] = Tabs\Tab::make($language->name)
                ->badge((string) $language->code)
                ->schema([
                    TextInput::make("localized_content.$language_id.title")
                        ->label(__('admin/settings/not_found_page_settings.labels.title'))
                        ->required()
                        ->maxLength(255),

                    Textarea::make("localized_content.$language_id.description")
                        ->label(__('admin/settings/not_found_page_settings.labels.description'))
                        ->rows(4)
                        ->nullable(),

                    Section::make(__('admin/settings/not_found_page_settings.sections.home_link'))
                        ->schema([
                            TextInput::make("localized_content.$language_id.link.label")
                                ->label(__('admin/settings/not_found_page_settings.labels.link_label'))
                                ->nullable()
                                ->maxLength(255),

                            TextInput::make("localized_content.$language_id.link.url")
                                ->label(__('admin/settings/not_found_page_settings.labels.link_url'))
                                ->helperText(__('admin/settings/not_found_page_settings.helpers.link_url'))
                                ->required()
                                ->maxLength(2048)
                                ->rules([
                                    'required',
                                    'string',
                                    'max:2048',
                                    'regex:/^(\/|https?:\/\/)/i',
                                ]),
                        ])
                        ->columns(1),
                ])
                ->columns(1);
        }

        return Tabs\Tab::make(__('admin/settings/not_found_page_settings.tabs.general'))
            ->schema([
                Section::make(__('admin/settings/not_found_page_settings.sections.localized_content'))
                    ->schema([
                        Tabs::make('NotFoundLocalizedContentTabs')
                            ->tabs($language_tabs)
                            ->activeTab(1)
                            ->contained(false),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    protected static function createImagesTab(): Tabs\Tab
    {
        $image_sections = [];
        $max_upload_image_size = (int)config('app.page_settings.not_found.for_admin.upload_max_size_kb');

        foreach (['slot_1', 'slot_2'] as $slot) {
            $image_sections[] = Section::make(__('admin/settings/not_found_page_settings.sections.' . $slot))
                ->schema([
                    FileUpload::make("images.$slot.path")
                        ->label(__('admin/settings/not_found_page_settings.labels.image'))
                        ->helperText(__('admin/settings/not_found_page_settings.helpers.image'))
                        ->image()
                        ->directory('images/not-found')
                        ->maxSize($max_upload_image_size)
                        ->rules([
                            'required',
                            Rule::file()::types(['image/jpeg', 'image/png', 'image/svg+xml']),
                            "max:$max_upload_image_size",
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
                            TextInput::make("images.$slot.width")
                                ->label(__('admin/settings/not_found_page_settings.labels.width'))
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            TextInput::make("images.$slot.height")
                                ->label(__('admin/settings/not_found_page_settings.labels.height'))
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            Toggle::make("images.$slot.is_square")
                                ->label(__('admin/settings/not_found_page_settings.labels.is_square'))
                                ->default(true)
                                ->required(),

                            TextInput::make("images.$slot.background")
                                ->label(__('admin/settings/not_found_page_settings.labels.background'))
                                ->helperText(__('admin/settings/not_found_page_settings.helpers.background'))
                                ->placeholder('transparent or #FFFFFF')
                                ->default('transparent')
                                ->required()
                                ->rules([
                                    'required',
                                    'string',
                                    'regex:/^(transparent|#[0-9a-fA-F]{6})$/',
                                ]),

                            TextInput::make("images.$slot.custom_css_classes")
                                ->label(__('admin/settings/not_found_page_settings.labels.custom_css_classes'))
                                ->helperText(__('admin/settings/not_found_page_settings.helpers.custom_css_classes'))
                                ->maxLength(1000)
                                ->nullable(),

                            TextInput::make("images.$slot.sort_order")
                                ->label(__('admin/settings/not_found_page_settings.labels.sort_order'))
                                ->numeric()
                                ->minValue(0)
                                ->required(),
                        ])
                        ->columns(),
                ])
                ->columns(1);
        }

        return Tabs\Tab::make(__('admin/settings/not_found_page_settings.tabs.images'))
            ->schema($image_sections)
            ->columns(1);
    }
}
