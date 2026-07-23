<?php

declare(strict_types=1);

namespace Modules\Carousel\Filament;

use App\Models\ApplicationSettings\Language;
use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Modules\Carousel\Support\CarouselConfig;

readonly class ModuleInstanceFormSchema
{
    public function __construct(
        private CarouselConfig $carousel_config,
    ) {
    }

    /**
     * @return array<int, Component>
     */
    public function getComponents(?ModuleDefinition $definition = null, ?ModuleInstance $instance = null): array
    {
        unset($definition, $instance);

        $active_languages = (new Language())->getActiveLanguages();

        return [
            Section::make()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->columnSpanFull()
                        ->label(__('carousel::admin/modules/carousel.labels.module_name'))
                        ->maxLength(255)
                        ->required(),

                    Grid::make()
                        ->columns()
                        ->columnSpanFull()
                        ->schema([
                            Select::make('placement')
                                ->label(__('carousel::admin/modules/carousel.labels.placement'))
                                ->options(config('app.modules_placements', []))
                                ->rules([
                                    'required',
                                    Rule::in(array_keys(config('app.modules_placements', []))),
                                ])
                                ->required()
                                ->native(false),

                            TextInput::make('sort_order')
                                ->label(__('carousel::admin/modules/carousel.labels.sort_order'))
                                ->numeric()
                                ->default(1),
                        ]),

                    Toggle::make('is_enabled')
                        ->columnSpanFull()
                        ->label(__('carousel::admin/modules/carousel.labels.is_enabled'))
                        ->default(true),

                    Hidden::make('context_key')
                        ->default(null),

                    Section::make(__('carousel::admin/modules/carousel.sections.visibility_and_image_sizes.title'))
                        ->description(__('carousel::admin/modules/carousel.sections.visibility_and_image_sizes.description'))
                        ->columnSpanFull()
                        ->schema([
                            Select::make('settings.shared.page_types')
                                ->label(__('carousel::admin/modules/carousel.labels.page_types'))
                                ->columnSpanFull()
                                ->multiple()
                                ->options($this->getPageTypeOptions())
                                ->required()
                                ->native(false),

                            Toggle::make('settings.shared.open_links_in_new_tab')
                                ->label(__('carousel::admin/modules/carousel.labels.open_links_in_new_tab'))
                                ->columnSpanFull()
                                ->default(true),

                            Grid::make()
                                ->columnSpanFull()
                                ->columnSpan(2)
                                ->schema([
                                    TextInput::make('settings.shared.desktop_image.width')
                                        ->label(__('carousel::admin/modules/carousel.labels.desktop_image_width'))
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.desktop_image.width', 1220))
                                        ->required()
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.desktop_image_width')),

                                    TextInput::make('settings.shared.desktop_image.height')
                                        ->label(__('carousel::admin/modules/carousel.labels.desktop_image_height'))
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.desktop_image.height', 720))
                                        ->required()
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.desktop_image_height')),

                                    TextInput::make('settings.shared.desktop_image.max_width')
                                        ->label(__('carousel::admin/modules/carousel.labels.desktop_image_max_width'))
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.desktop_image.max_width', 1920))
                                        ->required(),

                                    TextInput::make('settings.shared.desktop_image.max_height')
                                        ->label(__('carousel::admin/modules/carousel.labels.desktop_image_max_height'))
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.desktop_image.max_height', 1080))
                                        ->required(),

                                    TextInput::make('settings.shared.mobile_image.width')
                                        ->label(__('carousel::admin/modules/carousel.labels.mobile_image_width'))
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.mobile_image.width', 360))
                                        ->required()
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.mobile_image_width')),

                                    TextInput::make('settings.shared.mobile_image.height')
                                        ->label(__('carousel::admin/modules/carousel.labels.mobile_image_height'))
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.mobile_image.height', 640))
                                        ->required()
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.mobile_image_height')),

                                    TextInput::make('settings.shared.mobile_image.max_width')
                                        ->label(__('carousel::admin/modules/carousel.labels.mobile_image_max_width'))
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.mobile_image.max_width', 768))
                                        ->required(),

                                    TextInput::make('settings.shared.mobile_image.max_height')
                                        ->label(__('carousel::admin/modules/carousel.labels.mobile_image_max_height'))
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.mobile_image.max_height', 1280))
                                        ->required(),
                                ]),
                        ]),

                    Repeater::make('settings.slides')
                        ->label(__('carousel::admin/modules/carousel.labels.slides'))
                        ->default($this->getDefaultSlides($active_languages))
                        ->defaultItems(1)
                        ->reorderable()
                        ->cloneable()
                        ->columnSpanFull()
                        ->itemLabel(function (array $state): string {
                            $translations = Arr::get($state, 'translations', []);
                            $current_locale = app()->getLocale();

                            return Arr::get($translations, $current_locale . '.title')
                                ?? Arr::get($translations, array_key_first($translations) . '.title')
                                ?? __('carousel::admin/modules/carousel.labels.slide');
                        })
                        ->schema([
                            Section::make(__('carousel::admin/modules/carousel.sections.slide_state.title'))
                                ->description(__('carousel::admin/modules/carousel.sections.slide_state.description'))
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label(__('carousel::admin/modules/carousel.labels.slide_is_active'))
                                        ->default(true),

                                    TextInput::make('sort_order')
                                        ->label(__('carousel::admin/modules/carousel.labels.slide_sort_order'))
                                        ->numeric()
                                        ->default(1),
                                ]),

                            Tabs::make('SlideLanguageTabs')
                                ->tabs($this->getLanguageTabs($active_languages))
                                ->contained(false),
                        ])
                        ->required(),
                ]),
        ];
    }

    /**
     * @param  Collection<int, Language>  $active_languages
     * @return array<int, Tabs\Tab>
     */
    private function getLanguageTabs(Collection $active_languages): array
    {
        $tabs = [];

        foreach ($active_languages as $language) {
            $language_code = (string) $language->code;

            $tabs[] = Tabs\Tab::make($language->name)
                ->badge($language_code)
                ->schema([
                    Hidden::make("translations.$language_code.language_code")
                        ->default($language_code),

                    Grid::make()
                        ->columnSpan(2)
                        ->schema([
                            Grid::make()
                                ->schema([
                                    TextInput::make("translations.$language_code.title")
                                        ->label(__('carousel::admin/modules/carousel.labels.heading'))
                                        ->columnSpanFull()
                                        ->maxLength(255)
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.heading')),

                                    Textarea::make("translations.$language_code.description")
                                        ->label(__('carousel::admin/modules/carousel.labels.description'))
                                        ->columnSpanFull()
                                        ->rows(3)
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.description')),

                                    TextInput::make("translations.$language_code.button_text")
                                        ->label(__('carousel::admin/modules/carousel.labels.button_text'))
                                        ->columnSpanFull()
                                        ->maxLength(255)
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.button_text')),

                                    TextInput::make("translations.$language_code.image_url")
                                        ->label(__('carousel::admin/modules/carousel.labels.image_link'))
                                        ->columnSpanFull()
                                        ->url()
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.image_link')),

                                    TextInput::make("translations.$language_code.button_url")
                                        ->label(__('carousel::admin/modules/carousel.labels.button_link'))
                                        ->columnSpanFull()
                                        ->url()
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.button_link')),
                                ]),

                            Grid::make()
                                ->schema([
                                    FileUpload::make("translations.$language_code.desktop_image")
                                        ->label(__('carousel::admin/modules/carousel.labels.desktop_image'))
                                        ->columnSpanFull()
                                        ->image()
                                        ->visibility('public')
                                        ->directory((string) $this->carousel_config->get('uploads.directory', 'images/modules/carousel'))
                                        ->acceptedFileTypes((array) $this->carousel_config->get('uploads.accepted_mime_types', []))
                                        ->maxSize((int) $this->carousel_config->get('uploads.max_size_kb', 5120))
                                        ->rules([
                                            Rule::file()::types(['jpg', 'jpeg', 'png'])
                                                ->max((int) $this->carousel_config->get('uploads.max_size_kb', 5120)),
                                        ])
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.desktop_image')),

                                    FileUpload::make("translations.$language_code.mobile_image")
                                        ->label(__('carousel::admin/modules/carousel.labels.mobile_image'))
                                        ->columnSpanFull()
                                        ->image()
                                        ->visibility('public')
                                        ->directory((string) $this->carousel_config->get('uploads.directory', 'images/modules/carousel'))
                                        ->acceptedFileTypes((array) $this->carousel_config->get('uploads.accepted_mime_types', []))
                                        ->maxSize((int) $this->carousel_config->get('uploads.max_size_kb', 5120))
                                        ->rules([
                                            Rule::file()::types(['jpg', 'jpeg', 'png'])
                                                ->max((int) $this->carousel_config->get('uploads.max_size_kb', 5120)),
                                        ])
                                        ->helperText(__('carousel::admin/modules/carousel.helpers.mobile_image')),
                                ]),
                        ]),
                ]);
        }

        return $tabs;
    }

    /**
     * @return array<string, string>
     */
    private function getPageTypeOptions(): array
    {
        /** @var array<string, string> $page_types */
        $page_types = config('page-settings.page_type', []);

        return collect($page_types)
            ->mapWithKeys(function (string $value, string $key): array {
                $translation_key = 'carousel::admin/modules/carousel.options.page_types.' . $key;
                $translated_label = __($translation_key);

                return [
                    $value => $translated_label !== $translation_key
                        ? $translated_label
                        : ucfirst(str_replace('_', ' ', $key)),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, Language>  $active_languages
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultSlides(Collection $active_languages): array
    {
        return [
            [
                'is_active' => true,
                'sort_order' => 1,
                'translations' => $active_languages
                    ->mapWithKeys(function (Language $language): array {
                        return [
                            $language->code => [
                                'language_code' => $language->code,
                                'title' => '',
                                'description' => '',
                                'button_text' => '',
                                'button_url' => '',
                                'image_url' => '',
                                'desktop_image' => null,
                                'mobile_image' => null,
                            ],
                        ];
                    })
                    ->all(),
            ],
        ];
    }
}
