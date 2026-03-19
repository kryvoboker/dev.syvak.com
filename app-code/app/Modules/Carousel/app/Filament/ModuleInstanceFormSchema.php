<?php

declare(strict_types=1);

namespace Modules\Carousel\Filament;

use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use App\Models\ApplicationSettings\Language;
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
    ) {}

    /**
     * @return array<int, Component>
     */
    public function getComponents(?ModuleDefinition $definition = null, ?ModuleInstance $instance = null): array
    {
        $active_languages = new Language()->getActiveLanguages();

        return [
            Section::make()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->columnSpanFull()
                        ->label('Module name')
                        ->maxLength(255)
                        ->required(),

                    Grid::make()
                        ->columns()
                        ->columnSpanFull()
                        ->schema([
                            Select::make('placement')
                                ->label('Placement')
                                ->options(config('app.modules_placements', []))
                                ->rules([
                                    'required',
                                    Rule::in(array_keys(config('app.modules_placements', []))),
                                ])
                                ->required()
                                ->native(false),

                            TextInput::make('sort_order')
                                ->label('Module sort order')
                                ->numeric()
                                ->default(1),
                        ]),

                    Toggle::make('is_enabled')
                        ->columnSpanFull()
                        ->label('Module is active')
                        ->default(true),

                    Hidden::make('context_key')
                        ->default(null),

                    Section::make('Visibility and image sizes')
                        ->description('Only page placement, display pages, image sizes, and images are required. Text and links can be left empty.')
                        ->columnSpanFull()
                        ->schema([
                            Select::make('settings.shared.page_types')
                                ->label('Show on pages')
                                ->columnSpanFull()
                                ->multiple()
                                ->options($this->getPageTypeOptions())
                                ->required()
                                ->native(false),

                            Toggle::make('settings.shared.open_links_in_new_tab')
                                ->label('Open links in a new tab')
                                ->columnSpanFull()
                                ->default(true),

                            Grid::make()
                                ->columnSpanFull()
                                ->columnSpan(2)
                                ->schema([
                                    TextInput::make('settings.shared.desktop_image.width')
                                        ->label('Desktop image width')
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.desktop_image.width', 1220))
                                        ->required()
                                        ->helperText('Required field. Use the final image width for desktop slides.'),

                                    TextInput::make('settings.shared.desktop_image.height')
                                        ->label('Desktop image height')
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.desktop_image.height', 720))
                                        ->required()
                                        ->helperText('Required field. Use the final image height for desktop slides.'),

                                    TextInput::make('settings.shared.desktop_image.max_width')
                                        ->label('Desktop image max width')
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.desktop_image.max_width', 1920))
                                        ->required(),

                                    TextInput::make('settings.shared.desktop_image.max_height')
                                        ->label('Desktop image max height')
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.desktop_image.max_height', 1080))
                                        ->required(),

                                    TextInput::make('settings.shared.mobile_image.width')
                                        ->label('Mobile image width')
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.mobile_image.width', 360))
                                        ->required()
                                        ->helperText('Required field. Use the final image width for mobile slides.'),

                                    TextInput::make('settings.shared.mobile_image.height')
                                        ->label('Mobile image height')
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.mobile_image.height', 640))
                                        ->required()
                                        ->helperText('Required field. Use the final image height for mobile slides.'),

                                    TextInput::make('settings.shared.mobile_image.max_width')
                                        ->label('Mobile image max width')
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.mobile_image.max_width', 768))
                                        ->required(),

                                    TextInput::make('settings.shared.mobile_image.max_height')
                                        ->label('Mobile image max height')
                                        ->numeric()
                                        ->default((int) $this->carousel_config->get('storefront.mobile_image.max_height', 1280))
                                        ->required(),
                                ]),
                        ]),

                    Repeater::make('settings.slides')
                        ->label('Slides')
                        ->default($this->getDefaultSlides($active_languages))
                        ->defaultItems(1)
                        ->reorderable()
                        ->cloneable()
                        ->columnSpanFull()
                        ->itemLabel(function (array $state): string {
                            $translations   = Arr::get($state, 'translations', []);
                            $current_locale = app()->getLocale();

                            return Arr::get($translations, $current_locale . '.title')
                                ?? Arr::get($translations, array_key_first($translations) . '.title')
                                ?? 'Slide';
                        })
                        ->schema([
                            Section::make('Slide state')
                                ->description('Slide text and links are optional. If you leave them empty, they will not be shown in the storefront carousel.')
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label('Slide is active')
                                        ->default(true),

                                    TextInput::make('sort_order')
                                        ->label('Slide sort order')
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
                                        ->label('Heading')
                                        ->columnSpanFull()
                                        ->maxLength(255)
                                        ->helperText('Optional. Leave empty to hide the heading for this slide.'),

                                    Textarea::make("translations.$language_code.description")
                                        ->label('Description')
                                        ->columnSpanFull()
                                        ->rows(3)
                                        ->helperText('Optional. Leave empty to hide the description.'),

                                    TextInput::make("translations.$language_code.button_text")
                                        ->label('Button text')
                                        ->columnSpanFull()
                                        ->maxLength(255)
                                        ->helperText('Optional. The button is shown only when both text and button link are filled.'),

                                    TextInput::make("translations.$language_code.image_url")
                                        ->label('Image link')
                                        ->columnSpanFull()
                                        ->url()
                                        ->helperText('Optional. If empty, the image will not be clickable.'),

                                    TextInput::make("translations.$language_code.button_url")
                                        ->label('Button link')
                                        ->columnSpanFull()
                                        ->url()
                                        ->helperText('Optional. The button is shown only when both text and button link are filled.'),
                                ]),

                            Grid::make()
                                ->schema([
                                    FileUpload::make("translations.$language_code.desktop_image")
                                        ->label('Desktop image')
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
                                        ->helperText('Optional. Upload a desktop slide image for this language. If empty, the storefront fallback strategy will be applied.'),

                                    FileUpload::make("translations.$language_code.mobile_image")
                                        ->label('Mobile image')
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
                                        ->helperText('Optional. Upload a mobile slide image for this language. If empty, the storefront fallback strategy will be applied.'),
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
        $page_types = config('page-type', []);

        return collect($page_types)
            ->mapWithKeys(fn (string $value, string $key): array => [$value => ucfirst(str_replace('_', ' ', $key))])
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
                'is_active'    => true,
                'sort_order'   => 1,
                'translations' => $active_languages
                    ->mapWithKeys(function (Language $language): array {
                        return [
                            $language->code => [
                                'language_code' => $language->code,
                                'title'         => '',
                                'description'   => '',
                                'button_text'   => '',
                                'button_url'    => '',
                                'image_url'     => '',
                                'desktop_image' => null,
                                'mobile_image'  => null,
                            ],
                        ];
                    })
                    ->all(),
            ],
        ];
    }
}
