<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Schemas;

use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Catalogs\Products\ProductToAttribute;
use App\Models\Settings\Language;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

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
                        self::createImagesTab(),
                        self::createDiscountsTab(),
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
        return Tabs\Tab::make(__('admin/catalogs/products/products.tab_general'))
            ->schema([
                Section::make(__('admin/catalogs/products/products.section_basic_info'))
                    ->schema([
                        TextInput::make('model')
                            ->label(__('admin/catalogs/products/products.label_product_model'))
                            ->maxLength(64)
                            ->required(),

                        TextInput::make('sku')
                            ->label(__('admin/catalogs/products/products.label_sku'))
                            ->maxLength(64)
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('ean')
                            ->label(__('admin/catalogs/products/products.label_ean'))
                            ->maxLength(14)
                            ->default(null),
                    ])
                    ->columns(3),

                Section::make(__('admin/catalogs/products/products.section_stock'))
                    ->schema([
                        TextInput::make('quantity')
                            ->label(__('admin/catalogs/products/products.label_quantity'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        TextInput::make('minimum')
                            ->label(__('admin/catalogs/products/products.label_minimum'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('admin/catalogs/products/products.section_pricing'))
                    ->schema([
                        TextInput::make('price')
                            ->label(__('admin/catalogs/products/products.label_price'))
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->default(0.0)
                            ->required(),
                    ])
                    ->columns(1),

                Section::make(__('admin/catalogs/products/products.section_image'))
                    ->schema([
                        FileUpload::make('image')
                            ->label(__('admin/catalogs/products/products.label_image'))
                            ->image()
                            ->directory('products')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),
                    ])
                    ->columns(1),

                Section::make(__('admin/catalogs/products/products.section_settings'))
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('admin/catalogs/products/products.label_is_active'))
                            ->default(true)
                            ->required(),

                        DateTimePicker::make('date_available')
                            ->label(__('admin/catalogs/products/products.label_date_available'))
                            ->default(now()),

                        DateTimePicker::make('date_added')
                            ->label(__('admin/catalogs/products/products.label_date_added'))
                            ->default(now())
                            ->required(),

                        TextInput::make('viewed')
                            ->label(__('admin/catalogs/products/products.label_viewed'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
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
        return Tabs\Tab::make(__('admin/catalogs/products/products.tab_translations'))
            ->schema([
                Section::make(__('admin/catalogs/products/products.section_translations'))
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
        $tabs = [];

        foreach ($active_languages as $language) {
            $tabs[] = Tabs\Tab::make($language->name)
                ->schema([
                    Hidden::make("descriptions.{$language->id}.language_id")
                        ->default($language->id),

                    TextInput::make("descriptions.{$language->id}.name")
                        ->label(__('admin/catalogs/products/products.label_name'))
                        ->maxLength(255)
                        ->required(),

                    RichEditor::make("descriptions.{$language->id}.description")
                        ->label(__('admin/catalogs/products/products.label_description'))
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'link',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'blockquote',
                        ])
                        ->columnSpanFull(),

                    TextInput::make("descriptions.{$language->id}.meta_title")
                        ->label(__('admin/catalogs/products/products.label_meta_title'))
                        ->maxLength(255),

                    TextInput::make("descriptions.{$language->id}.meta_description")
                        ->label(__('admin/catalogs/products/products.label_meta_description'))
                        ->maxLength(255),

                    TextInput::make("descriptions.{$language->id}.meta_keywords")
                        ->label(__('admin/catalogs/products/products.label_meta_keywords'))
                        ->maxLength(255),
                ])
                ->badge($language->code)
                ->columns(2);
        }

        return $tabs;
    }

    /**
     * Create images tab
     *
     * @return Tabs\Tab
     */
    protected static function createImagesTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/catalogs/products/products.tab_images'))
            ->schema([
                Section::make(__('admin/catalogs/products/products.section_additional_images'))
                    ->schema([
                        Repeater::make('images')
                            ->label(__('admin/catalogs/products/products.label_images'))
                            ->schema([
                                FileUpload::make('image')
                                    ->label(__('admin/catalogs/products/products.label_image'))
                                    ->image()
                                    ->directory('products/gallery')
                                    ->maxSize(2048)
                                    ->imageEditor()
                                    ->required(),

                                TextInput::make('sort_order')
                                    ->label(__('admin/catalogs/products/products.label_sort_order'))
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->collapsible()
                            ->cloneable()
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->addActionLabel(__('admin/catalogs/products/products.label_add_image'))
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Create discounts tab
     *
     * @return Tabs\Tab
     */
    protected static function createDiscountsTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/catalogs/products/products.tab_discounts'))
            ->schema([
                Section::make(__('admin/catalogs/products/products.section_discounts'))
                    ->schema([
                        Repeater::make('discounts')
                            ->label(__('admin/catalogs/products/products.label_discounts'))
                            ->schema([
                                Select::make('user_group_id')
                                    ->label(__('admin/catalogs/products/products.label_user_group'))
                                    ->options([
                                        1 => 'Default',
                                        2 => 'Wholesale',
                                        3 => 'Retail',
                                    ])
                                    ->default(1)
                                    ->required(),

                                TextInput::make('quantity')
                                    ->label(__('admin/catalogs/products/products.label_discount_quantity'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                TextInput::make('priority')
                                    ->label(__('admin/catalogs/products/products.label_priority'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),

                                TextInput::make('price')
                                    ->label(__('admin/catalogs/products/products.label_discount_price'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->required(),

                                DateTimePicker::make('date_start')
                                    ->label(__('admin/catalogs/products/products.label_date_start'))
                                    ->required(),

                                DateTimePicker::make('date_end')
                                    ->label(__('admin/catalogs/products/products.label_date_end'))
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->collapsible()
                            ->cloneable()
                            ->reorderable(false)
                            ->addActionLabel(__('admin/catalogs/products/products.label_add_discount'))
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
                ->title(__('admin/catalogs/products/products.error_title'))
                ->body(__('admin/catalogs/products/products.error_no_language'))
                ->danger()
                ->send();
        }

        return Tabs\Tab::make(__('admin/catalogs/products/products.tab_attributes'))
            ->schema([
                Section::make(__('admin/catalogs/products/products.section_attributes'))
                    ->schema([
                        Repeater::make('attributes')
                            ->label(__('admin/catalogs/products/products.label_attributes'))
                            ->schema([
                                Select::make('attribute_id')
                                    ->label(__('admin/catalogs/products/products.label_attribute'))
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
                                                    ?? "Attribute #{$attribute->id}";

                                                return [$attribute->id => $name];
                                            });
                                    })
                                    ->getOptionLabelUsing(function ($value) use ($current_language_id) {
                                        if ($value === null || $current_language_id === null) {
                                            return '-';
                                        }

                                        $attribute = Attribute::with([
                                            'attributeDescription' => function ($query) use ($current_language_id) {
                                                $query->where('language_id', $current_language_id);
                                            }
                                        ])->find($value);

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
                                    ->label(__('admin/catalogs/products/products.label_language'))
                                    ->options($active_languages->pluck('name', 'id'))
                                    ->default($current_language_id)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // Auto-validate uniqueness on change
                                    }),

                                TextInput::make('text')
                                    ->label(__('admin/catalogs/products/products.label_attribute_text'))
                                    ->maxLength(255)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->collapsible()
                            ->cloneable()
                            ->reorderable(false)
                            ->addActionLabel(__('admin/catalogs/products/products.label_add_attribute'))
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
}
