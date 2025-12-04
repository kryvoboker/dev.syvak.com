<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Schemas;

use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Catalogs\Categories\Category;
use App\Models\Settings\Language;
use App\Models\Users\UserGroup;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Throwable;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $active_languages = new Language()->getActiveLanguages();

        return $schema
            ->components([
                Tabs::make('ProductTabs')
                    ->tabs([
                        self::createGeneralTab(),
                        self::createTranslationsTab($active_languages),
                        self::createCategoriesTab($active_languages),
                        self::createImagesTab(),
                        self::createDiscountsTab($active_languages),
                        self::createAttributesTab($active_languages),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Create general information tab
     *
     * @return Tabs\Tab
     */
    protected static function createGeneralTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/default.tabs.general'))
            ->schema([
                Section::make(__('admin/default.sections.basic_info'))
                    ->schema([
                        TextInput::make('model')
                            ->label(__('admin/default.labels.model'))
                            ->maxLength(255)
                            ->rules(['required', 'string', 'max:255'])
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('sku')
                            ->label(__('admin/default.labels.sku'))
                            ->maxLength(255)
                            ->rules(['required', 'string', 'max:255'])
                            ->required(),

                        TextInput::make('ean')
                            ->label(__('admin/default.labels.ean'))
                            ->maxLength(255)
                            ->rules(['nullable', 'string', 'numeric', 'max:255'])
                            ->default(null),
                    ])
                    ->columns(3),

                Section::make(__('admin/default.sections.stock'))
                    ->schema([
                        TextInput::make('quantity')
                            ->label(__('admin/default.labels.quantity'))
                            ->numeric()
                            ->rules(['required', 'numeric', 'min:0'])
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        TextInput::make('minimum')
                            ->label(__('admin/default.labels.minimum'))
                            ->numeric()
                            ->rules(['required', 'numeric', 'min:1'])
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                    ])
                    ->columns(),

                Section::make(__('admin/default.sections.pricing'))
                    ->schema([
                        TextInput::make('price')
                            ->label(__('admin/default.labels.price'))
                            ->numeric()
                            ->minValue(0)
                            ->rules(['required', 'numeric', 'min:0'])
                            ->prefix(config('app.currency.default_currency_symbol'))
                            ->default(0.0)
                            ->required(),
                    ])
                    ->columns(1),

                Section::make(__('admin/default.sections.image'))
                    ->schema([
                        FileUpload::make('image')
                            ->label(__('admin/default.labels.image'))
                            ->image() // accept images only
                            ->directory(config('app.images.product.image_path'))
                            ->maxSize((int)config('app.images.product.upload.max_size_kb'))
                            ->rules(['nullable', 'image', 'max:' . (int)config('app.images.product.upload.max_size_kb')])
                            ->preserveFilenames() // not generate unique names
                            ->imageEditor()
                            ->imageEditorViewportWidth((int)config('app.images.product.preview_in_page_in_admin.width'))
                            ->imageEditorViewportHeight((int)config('app.images.product.preview_in_page_in_admin.height'))
                            ->imageEditorAspectRatios([
                                '1:1'  => '1:1',
                                '4:3'  => '4:3',
                                '16:9' => '16:9',
                            ])
                            ->nullable()
                            ->default(null),
                    ])
                    ->columns(1),

                Section::make(__('admin/default.sections.settings'))
                    ->schema([
                        TextInput::make('viewed')
                            ->label(__('admin/default.labels.viewed'))
                            ->numeric()
                            ->rules(['required', 'numeric', 'min:0'])
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('date_available')
                                    ->label(__('admin/default.labels.date_available'))
                                    ->rules(['required', 'date'])
                                    ->default(now(config('app.timezone')))
                                    ->required(),

                                DateTimePicker::make('date_added')
                                    ->label(__('admin/default.labels.date_added'))
                                    ->rules(['required', 'date'])
                                    ->default(now(config('app.timezone')))
                                    ->required(),
                            ]),

                        Toggle::make('is_active')
                            ->label(__('admin/default.labels.is_active'))
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(1),
            ]);
    }

    /**
     * Create translations tab with language tabs
     *
     * @param Collection<Language> $active_languages
     *
     * @return Tabs\Tab
     */
    protected static function createTranslationsTab(Collection $active_languages): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/default.tabs.translations'))
            ->schema([
                Section::make(__('admin/default.sections.translations'))
                    ->schema([
                        Tabs::make('LanguageTabs')
                            ->tabs(self::createLanguageTabs($active_languages))
                            ->activeTab(1)
                            ->contained(false)
                            ->persistTabInQueryString(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Create language tabs for translations
     *
     * @param Collection<Language> $active_languages
     *
     * @return array<Tabs\Tab>
     */
    protected static function createLanguageTabs(Collection $active_languages): array
    {
        $tabs            = [];
        $total_languages = $active_languages->count();

        foreach ($active_languages as $language) {
            $tabs[] = Tabs\Tab::make($language->name)
                ->schema([
                    Hidden::make("descriptions.$language->id.language_id")
                        ->default($language->id),

                    TextInput::make("descriptions.$language->id.name")
                        ->label(__('admin/default.labels.name'))
                        ->maxLength(255)
                        ->rules(['required', 'string', 'max:255'])
                        ->columnSpanFull()
                        ->required(),

                    RichEditor::make("descriptions.$language->id.description")
                        ->label(__('admin/default.labels.description'))
                        ->toolbarButtons([
                            ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                            ['h1', 'h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd', 'alignJustify', 'textColor'],
                            ['blockquote', 'bulletList', 'orderedList'],
                            ['table'],
                            ['undo', 'redo', 'clearFormatting'],
                        ])
                        ->rules(['nullable'])
                        ->columnSpanFull(),

                    TextInput::make("descriptions.$language->id.meta_title")
                        ->label(__('admin/default.labels.meta_title'))
                        ->rules(['nullable', 'string', 'max:255'])
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make("descriptions.$language->id.meta_description")
                        ->label(__('admin/default.labels.meta_description'))
                        ->rules(['nullable', 'string', 'max:255'])
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make("descriptions.$language->id.meta_keywords")
                        ->label(__('admin/default.labels.meta_keywords'))
                        ->rules(['nullable', 'string', 'max:255'])
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->badge($language->code)
                ->columns(min($total_languages, 4));
        }

        return $tabs;
    }

    /**
     * Create categories tab
     *
     * @param Collection<Language> $active_languages
     *
     * @return Tabs\Tab
     */
    protected static function createCategoriesTab(Collection $active_languages): Tabs\Tab
    {
        $current_language_id = self::tryGetCurrentLanguageId($active_languages);

        return Tabs\Tab::make(__('admin/default.tabs.categories'))
            ->schema([
                Section::make(__('admin/default.sections.categories'))
                    ->schema([
                        Select::make('categories')
                            ->label(__('admin/default.labels.categories'))
                            ->multiple()
                            ->relationship('categories', 'id')
                            ->options(function () use ($current_language_id) {
                                if ($current_language_id === null) {
                                    return [];
                                }

                                return self::getCategoryHierarchy($current_language_id);
                            })
                            ->getOptionLabelUsing(function ($value) use ($current_language_id) {
                                if ($value === null || $current_language_id === null) {
                                    return '-';
                                }

                                return self::getCategoryFullPath($value, $current_language_id);
                            })
                            ->searchable()
                            ->preload()
                            ->helperText(__('admin/default.helpers.categories'))
                            ->distinct()
                            ->rules([
                                fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                    if (is_array($value) && count($value) !== count(array_unique($value))) {
                                        $fail(__('admin/default.errors.validation_duplicate_categories'));
                                    }
                                },
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Get category hierarchy with full paths
     *
     * @param int $language_id
     *
     * @return array<int, string>
     * @throws Throwable
     */
    protected static function getCategoryHierarchy(int $language_id): array
    {
        $categories = new Category()->getActiveCategoriesWithDescriptionsByLanguageId($language_id);

        $hierarchy = [];

        foreach ($categories as $category) {
            $path                     = self::getCategoryFullPath($category->id, $language_id);
            $hierarchy[$category->id] = $path;
        }

        return $hierarchy;
    }

    /**
     * Get full category path (Parent > Child > Grandchild)
     *
     * @param int $category_id
     * @param int $language_id
     *
     * @return string
     * @throws Throwable
     */
    protected static function getCategoryFullPath(int $category_id, int $language_id): string
    {
        $category_instance = new Category();

        $category = $category_instance->getActiveCategoryWithDescriptionByCategoryIdAndLanguageId(
            $category_id,
            $language_id
        );

        if ($category === null) {
            return "Category #$category_id";
        }

        $path             = [];
        $total_iterations = 0;
        $current_category = $category;

        // Build path from current to root
        while ($current_category !== null) {
            throw_if(
                $total_iterations > 100,
                'Exception',
                __('admin/default.errors.something_went_wrong')
            );

            $total_iterations++;

            $description = $current_category->categoryDescription
                ->firstWhere('language_id', $language_id);

            $name = $description?->name
                ?? $current_category->categoryDescription->first()?->name
                ?? "Category #$current_category->id";

            array_unshift($path, $name);

            if ($current_category->parent_id !== null) {
                $current_category = $category_instance->getActiveCategoryWithDescriptionByCategoryIdAndLanguageId(
                    (int)$current_category->parent_id,
                    $language_id
                );
            } else {
                $current_category = null;
            }
        }

        return implode(' > ', $path);
    }

    /**
     * Create images tab
     *
     * @return Tabs\Tab
     */
    protected static function createImagesTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/default.tabs.images'))
            ->schema([
                Section::make(__('admin/default.sections.additional_images'))
                    ->schema([
                        Repeater::make('images')
                            ->label(__('admin/default.labels.images'))
                            ->schema([
                                FileUpload::make('image')
                                    ->label(__('admin/default.labels.image'))
                                    ->image() // accept images only
                                    ->directory(config('app.images.product.image_path'))
                                    ->maxSize((int)config('app.images.product.upload.max_size_kb'))
                                    ->rules(['image', 'max:' . (int)config('app.images.product.upload.max_size_kb')])
                                    ->preserveFilenames() // not generate unique names
                                    ->imageEditor()
                                    ->imageEditorViewportWidth((int)config('app.images.product.preview_in_page_in_admin.width'))
                                    ->imageEditorViewportHeight((int)config('app.images.product.preview_in_page_in_admin.height'))
                                    ->imageEditorAspectRatios([
                                        '1:1'  => '1:1',
                                        '4:3'  => '4:3',
                                        '16:9' => '16:9',
                                    ])
                                    ->required(),

                                TextInput::make('sort_order')
                                    ->label(__('admin/default.labels.sort_order'))
                                    ->numeric()
                                    ->rules(['nullable', 'numeric', 'min:0'])
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columns()
                            ->defaultItems(0)
                            ->collapsible()
                            ->cloneable()
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->addActionLabel(__('admin/default.labels.add_image'))
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Tab
     */
    protected static function createDiscountsTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/default.tabs.discounts'))
            ->schema([
                Section::make(__('admin/default.sections.discounts'))
                    ->schema([
                        Repeater::make('discounts')
                            ->label(__('admin/default.labels.discounts'))
                            ->schema([
                                Select::make('user_group_id')
                                    ->label(__('admin/default.labels.user_group'))
                                    ->options(function () {
                                        return new UserGroup()
                                            ->getActiveUserGroups()
                                            ->mapWithKeys(function (UserGroup $user_group) {
                                                $name = $user_group->name ?: "User Group #$user_group->id";

                                                return [$user_group->id => $name];
                                            });
                                    })
                                    ->rules(['required', 'numeric', Rule::exists('user_groups', 'id')])
                                    ->required(),

                                TextInput::make('quantity')
                                    ->label(__('admin/default.labels.discount_quantity'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->rules(['required', 'numeric', 'min:1'])
                                    ->default(1)
                                    ->required(),

                                TextInput::make('priority')
                                    ->label(__('admin/default.labels.priority'))
                                    ->numeric()
                                    ->rules(['required', 'numeric', 'min:0'])
                                    ->minValue(0)
                                    ->default(1)
                                    ->required(),

                                TextInput::make('price')
                                    ->label(__('admin/default.labels.discount_price'))
                                    ->numeric()
                                    ->rules(['required', 'numeric', 'min:0'])
                                    ->minValue(0)
                                    ->prefix(config('app.currency.default_currency_symbol'))
                                    ->required(),

                                DateTimePicker::make('date_start')
                                    ->label(__('admin/default.labels.date_start'))
                                    ->rules(['required', 'date'])
                                    ->required(),

                                DateTimePicker::make('date_end')
                                    ->label(__('admin/default.labels.date_end'))
                                    ->rules(['required', 'date'])
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->collapsible()
                            ->cloneable()
                            ->reorderable(false)
                            ->addActionLabel(__('admin/default.labels.add_discount'))
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Create attributes tab
     *
     * @param Collection<Language> $active_languages
     *
     * @return Tabs\Tab
     */
    protected static function createAttributesTab(Collection $active_languages): Tabs\Tab
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

        return Tabs\Tab::make(__('admin/default.tabs.attributes'))
            ->schema([
                Section::make(__('admin/default.sections.attributes'))
                    ->schema([
                        Repeater::make('attributes')
                            ->label(__('admin/default.labels.attributes'))
                            ->schema([
                                Select::make('attribute_id')
                                    ->label(__('admin/default.labels.attribute'))
                                    ->options(function () use ($current_language_id) {
                                        if ($current_language_id === null) {
                                            return [];
                                        }

                                        return new Attribute()
                                            ->getActiveAttributesWithDescriptionsByLanguageId($current_language_id)
                                            ->mapWithKeys(function (Attribute $attribute) use ($current_language_id) {
                                                $description = $attribute->attributeDescription
                                                    ->firstWhere('language_id', $current_language_id);

                                                $name = $description?->name
                                                    ?? $attribute->attributeDescription->first()?->name
                                                    ?? "Attribute #$attribute->id";

                                                return [$attribute->id => $name];
                                            });
                                    })
                                    ->getOptionLabelUsing(function ($attribute_id) use ($current_language_id) {
                                        if ($attribute_id === null || $current_language_id === null) {
                                            return '-';
                                        }

                                        $attribute = new Attribute()->getActiveAttributeWithDescriptionByAttributeIdAndLanguageId(
                                            $attribute_id,
                                            $current_language_id
                                        );

                                        if ($attribute === null) {
                                            return '-';
                                        }

                                        $description = $attribute->attributeDescription
                                            ->firstWhere('language_id', $current_language_id);

                                        return $description?->name
                                            ?? $attribute->attributeDescription->first()?->name
                                            ?? "Attribute #$attribute->id";
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // Auto-validate uniqueness on change
                                    }),

                                Select::make('language_id')
                                    ->label(__('admin/default.labels.language'))
                                    ->options($active_languages->pluck('name', 'id'))
                                    ->default($current_language_id)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // Auto-validate uniqueness on change
                                    }),

                                TextInput::make('text')
                                    ->label(__('admin/default.labels.attribute_text'))
                                    ->maxLength(255)
                                    ->rules(['required', 'string', 'max:255'])
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->collapsible()
                            ->cloneable()
                            ->reorderableWithDragAndDrop()
                            ->addActionLabel(__('admin/default.labels.add_attribute'))
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                return $data;
                            }),
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
