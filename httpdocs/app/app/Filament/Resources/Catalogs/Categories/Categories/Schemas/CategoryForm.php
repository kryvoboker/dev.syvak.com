<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Schemas;

use App\Models\Catalogs\Categories\Category;
use App\Models\Settings\Language;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class CategoryForm
{
    /**
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        $active_languages = new Language()->getActiveLanguages();

        return $schema
            ->components([

                Tabs::make('ProductTabs')
                    ->tabs([
                        self::createGeneralTab($active_languages),
                        self::createMetaTextsTab($active_languages),
                        self::createImagesTab(),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),

            ]);
    }

    /**
     * Create language tabs for specific section
     *
     * @param Collection<Language> $active_languages
     *
     * @return array<Tabs>
     */
    protected static function createGeneralLanguageTabs(Collection $active_languages): array
    {
        $tabs = [];

        foreach ($active_languages as $language) {
            $tabs[] = Tabs\Tab::make($language->name)
                ->schema([
                    Hidden::make("descriptions.$language->id.language_id")
                        ->default($language->id),

                    TextInput::make("descriptions.$language->id.name")
                        ->label(__('admin/default.labels.name'))
                        ->maxLength(255)
                        ->rules(['required', 'string', 'max:255'])
                        ->required(),

                    Textarea::make("descriptions.$language->id.description")
                        ->label(__('admin/default.labels.description'))
                        ->rows(5)
                        ->rules(['nullable', 'string']),
                ])
                ->badge($language->code);
        }

        return $tabs;
    }

    /**
     * @param Collection $active_languages
     *
     * @return Tabs\Tab
     */
    protected static function createGeneralTab(Collection $active_languages): Tabs\Tab
    {
        $current_language_id = self::tryGetCurrentLanguageId($active_languages);

        if ($current_language_id === null) {
            return Tabs\Tab::make(__('admin/default.tabs.categories'))
                ->schema([]);
        }

        return Tabs\Tab::make(__('admin/default.tabs.general'))
            ->schema([
                Section::make(__('admin/default.sections.basic_info'))
                    ->schema([
                        Select::make('parent_id')
                            ->label(__('admin/default.labels.parent_category'))
                            ->options(function (?Category $record) use ($current_language_id) {
                                $query = Category::query()
                                    ->with([
                                        'categoryDescription' => function (HasMany $query) use ($current_language_id) {
                                            $query->where('language_id', $current_language_id);
                                        }
                                    ])
                                    ->where('is_active', true)
                                    ->orderBy('sort_order');

                                // Exclude the current category when editing
                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }

                                return $query->get()
                                    ->mapWithKeys(function (Category $category) {
                                        $name = $category->categoryDescription->first()?->name ?? "Category #$category->id";

                                        return [$category->id => $name];
                                    });
                            })
                            ->searchable()
                            ->nullable()
                            ->placeholder(__('admin/default.placeholders.select_parent_category'))
                            ->helperText(__('admin/default.helpers.parent_category')),

                        Tabs::make('language_tabs_general')
                            ->tabs(self::createGeneralLanguageTabs($active_languages))
                            ->activeTab(1)
                            ->contained(false)
                            ->persistTabInQueryString(),

                        Toggle::make('is_active')
                            ->label(__('admin/default.labels.is_active'))
                            ->default(true)
                            ->required(),

                        TextInput::make('sort_order')
                            ->label(__('admin/default.labels.sort_order'))
                            ->numeric()
                            ->default(1)
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Tabs\Tab
     */
    protected static function createImagesTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/default.tabs.images'))
            ->schema([
                Section::make(__('admin/default.sections.images'))
                    ->schema([
                        FileUpload::make('icon')
                            ->label(__('admin/default.labels.icon'))
                            ->image() // accept images only
                            ->directory(config('app.images.category.image_path'))
                            ->maxSize((int)config('app.images.category.upload.max_size_kb'))
                            ->rules(['nullable', Rule::file()->types(['image/jpeg', 'image/png', 'image/svg+xml']), 'max:' . (int)config('app.images.category.upload.max_size_kb')])
                            ->preserveFilenames() // not generate unique names
                            ->imageEditor()
                            ->imageEditorViewportWidth((int)config('app.images.category.preview_in_page_in_admin.width'))
                            ->imageEditorViewportHeight((int)config('app.images.category.preview_in_page_in_admin.height'))
                            ->imageEditorAspectRatios([
                                '1:1'  => '1:1',
                                '4:3'  => '4:3',
                                '16:9' => '16:9',
                            ])
                            ->nullable()
                            ->default(null),

                        FileUpload::make('preview_image')
                            ->label(__('admin/default.labels.image'))
                            ->image() // accept images only
                            ->directory(config('app.images.category.image_path'))
                            ->maxSize((int)config('app.images.category.upload.max_size_kb'))
                            ->rules(['image', 'max:' . (int)config('app.images.category.upload.max_size_kb')])
                            ->preserveFilenames() // not generate unique names
                            ->imageEditor()
                            ->imageEditorViewportWidth((int)config('app.images.category.preview_in_page_in_admin.width'))
                            ->imageEditorViewportHeight((int)config('app.images.category.preview_in_page_in_admin.height'))
                            ->imageEditorAspectRatios([
                                '1:1'  => '1:1',
                                '4:3'  => '4:3',
                                '16:9' => '16:9',
                            ]),
                    ])
                    ->columns()
            ]);
    }

    /**
     * @param Collection $active_languages
     *
     * @return array
     */
    protected static function createMetaTextsLanguageTabs(Collection $active_languages): array
    {
        $tabs = [];

        foreach ($active_languages as $language) {
            $tabs[] = Tabs\Tab::make($language->name)
                ->schema([
                    Hidden::make("descriptions.$language->id.language_id")
                        ->default($language->id),

                    TextInput::make("descriptions.$language->id.h1_title")
                        ->label(__('admin/default.labels.h1_title'))
                        ->maxLength(255)
                        ->rules(['nullable', 'string', 'max:255']),

                    TextInput::make("descriptions.$language->id.meta_title")
                        ->label(__('admin/default.labels.meta_title'))
                        ->maxLength(255)
                        ->rules(['nullable', 'string', 'max:255']),

                    Textarea::make("descriptions.$language->id.meta_description")
                        ->label(__('admin/default.labels.meta_description'))
                        ->rows(2)
                        ->rules(['nullable', 'string', 'max:255']),

                    Textarea::make("descriptions.$language->id.meta_keywords")
                        ->label(__('admin/default.labels.meta_keywords'))
                        ->rows(2)
                        ->rules(['nullable', 'string', 'max:255']),
                ])
                ->badge($language->code);
        }

        return $tabs;
    }

    /**
     * @param Collection $active_languages
     *
     * @return Tabs\Tab
     */
    protected static function createMetaTextsTab(Collection $active_languages): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/default.tabs.meta_texts'))
            ->schema([
                Section::make(__('admin/default.sections.meta_texts'))
                    ->schema([
                        Tabs::make('language_tabs_meta_texts')
                            ->tabs(self::createMetaTextsLanguageTabs($active_languages))
                            ->activeTab(1)
                            ->contained(false)
                            ->persistTabInQueryString(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param Collection $active_languages
     *
     * @return int|null
     */
    private static function tryGetCurrentLanguageId(Collection $active_languages): ?int
    {
        /** @var Language $language */
        $language            = $active_languages->where('is_default', true)->first();
        $current_language_id = $language?->id;

        if ($current_language_id === null) {
            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/default.errors.no_language'))
                ->danger()
                ->send();
        }

        return $current_language_id;
    }
}
