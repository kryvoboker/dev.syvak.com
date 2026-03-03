<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Schemas;

use App\Filament\Resources\Trait\Forms\CommonTextFormTrait;
use App\Filament\Resources\Trait\Forms\DateFormTrait;
use App\Filament\Resources\Trait\Forms\ImageFormTrait;
use App\Filament\Resources\Trait\Forms\MetaTextFormTrait;
use App\Filament\Resources\Trait\Forms\NumericFormTrait;
use App\Filament\Resources\Trait\Forms\SelectFormTrait;
use App\Filament\Resources\Trait\Forms\SlugFormTrait;
use App\Filament\Resources\Trait\Forms\SortOrderFormTrait;
use App\Filament\Resources\Trait\Forms\ToggleCheckboxFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Categories\CategoryPath;
use App\Models\Settings\Language;
use App\Models\Users\UserGroup;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Throwable;

class ProductForm
{
    use LanguageTrait, SlugFormTrait, MetaTextFormTrait,
        ToggleCheckboxFormTrait, SortOrderFormTrait,
        ImageFormTrait, DateFormTrait, CommonTextFormTrait,
        NumericFormTrait, SelectFormTrait;

    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getAcriveLanguages();

        return $schema
            ->components([
                Tabs::make('ProductTabs')
                    ->tabs([
                        self::createGeneralTabs(),
                        self::createTranslationsFormTabs($active_languages),
                        self::createMetaTextsFormTabs($active_languages),
                        self::createCategoriesTabs($active_languages),
                        self::createImagesTabs(),
                        self::createDiscountsTabs(),
                        self::createAttributesTabs($active_languages),
                        self::createSlugsFormTabs($active_languages),
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
    protected static function createGeneralTabs(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/default.tabs.general'))
            ->schema([
                Section::make(__('admin/default.sections.basic_info'))
                    ->schema([
                        self::getTextFormField([
                            'field_name' => 'model',
                            'label'      => __('admin/default.labels.model'),
                            'max_length' => 255,
                            'rules'      => ['required', 'string', 'max:255'],
                            'unique'     => [
                                'ignore_record' => true,
                            ],
                        ]),

                        self::getTextFormField([
                            'field_name' => 'sku',
                            'label'      => __('admin/default.labels.sku'),
                            'max_length' => 255,
                            'rules'      => ['required', 'string', 'max:255'],
                        ]),

                        self::getEanField(),
                    ])
                    ->columns(3),

                Section::make(__('admin/default.sections.stock'))
                    ->schema([
                        self::getNumericFormField([
                            'field_name' => 'quantity',
                            'label'      => __('admin/default.labels.quantity'),
                        ]),

                        self::getNumericFormField([
                            'field_name' => 'minimum',
                            'label'      => __('admin/default.labels.minimum'),
                            'rules'      => ['required', 'numeric', 'min:1'],
                            'min_value'  => 1,
                            'default'    => 1,
                        ]),
                    ])
                    ->columns(),

                Section::make(__('admin/default.sections.pricing'))
                    ->schema([
                        self::getPriceFormField([
                            'field_name' => 'price',
                            'label'      => __('admin/default.labels.price'),
                            'rules'      => ['nullable', 'numeric', 'min:0'],
                        ]),
                    ])
                    ->columns(1),

                Section::make(__('admin/default.sections.image'))
                    ->schema([
                        self::getImageFormField(),
                    ])
                    ->columns(1),

                Section::make(__('admin/default.sections.settings'))
                    ->schema([
                        self::getNumericFormField([
                            'field_name' => 'viewed',
                            'label'      => __('admin/default.labels.viewed'),
                        ]),

                        Grid::make()
                            ->schema([
                                self::getDateAvailableFormField(),

                                self::getDateAddedFormField(),
                            ]),

                        self::getIsActiveFormField(),
                    ])
                    ->columns(1),
            ]);
    }

    /**
     * Create categories tab
     *
     * @param Collection<Language> $active_languages
     *
     * @return Tabs\Tab
     */
    protected static function createCategoriesTabs(Collection $active_languages): Tabs\Tab
    {
        $current_language_id = self::tryGetCurrentLanguageIdFromActiveLangs($active_languages);

        if ($current_language_id === null) {
            return Tabs\Tab::make(__('admin/default.tabs.categories'))
                ->schema([]);
        }

        return Tabs\Tab::make(__('admin/default.tabs.categories'))
            ->schema([
                Section::make(__('admin/default.sections.categories'))
                    ->schema([
                        self::getMultipleSelectFormField([
                            'field_name'                => 'categories',
                            'label'                     => __('admin/default.labels.categories'),
                            'helper_text'               => __('admin/default.helpers.categories'),
                            'relationship'              => [
                                'name'  => 'categories',
                                'title' => 'id',
                            ],
                            'options'                   => function () use ($current_language_id) {
                                if ($current_language_id === null) {
                                    return [];
                                }

                                return self::getCategoryHierarchy($current_language_id);
                            },
                            'get_option_label_using_cb' => function ($value) use ($current_language_id) {
                                if ($value === null || $current_language_id === null) {
                                    return '-';
                                }

                                return self::getCategoryFullPath($value, $current_language_id);
                            },
                            'rules'                     => [
                                fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                    if (is_array($value) && count($value) !== count(array_unique($value))) {
                                        $fail(__('admin/default.errors.validation_duplicate_categories'));
                                    }
                                },
                            ],
                            'preload'                   => true,
                            'distinct'                  => true,
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
        $categories = new Category()->getActiveCategoriesWithDescriptionsAndPathByLanguageId($language_id);

        $hierarchy = [];

        foreach ($categories as $category) {
            $path = self::getCategoryFullPath($category->id, $language_id);

            $hierarchy[$category->id] = $path;
        }

        return $hierarchy;
    }

    /**
     * Get full category path using category_paths table (Parent > Child > Grandchild)
     *
     * @param int $category_id
     * @param int $language_id
     *
     * @return string
     * @throws Throwable
     */
    protected static function getCategoryFullPath(int $category_id, int $language_id): string
    {
        // Get all path IDs for this category ordered by level (root first)
        $path_ids = new CategoryPath()->getPathIdsByCategoryId($category_id)
            ->pluck('path_id')
            ->toArray();

        throw_if(
            empty($path_ids),
            'Exception',
            __('admin/default.errors.category_path_not_found', ['id' => $category_id])
        );

        // Get all categories in the path with descriptions
        $categories = new Category()->getActiveCategoriesWithDescriptionsByLanguageIdAndPathIds($language_id, $path_ids)
            ->keyBy('id');

        $path = [];

        // Build path in correct order (from root to current)
        foreach ($path_ids as $path_id) {
            /** @var Category $category */
            $category = $categories->get($path_id);

            if ($category === null) {
                continue;
            }

            $description = $category->categoryDescription
                ->firstWhere('language_id', $language_id);

            $name = $description?->name
                ?? $category->categoryDescription->first()?->name
                ?? "Category #$category->id";

            $path[] = $name;
        }

        return implode(' > ', array_reverse($path));
    }

    /**
     * Create images tab
     *
     * @return Tabs\Tab
     */
    protected static function createImagesTabs(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/default.tabs.images'))
            ->schema([
                Section::make(__('admin/default.sections.additional_images'))
                    ->schema([
                        Repeater::make('images')
                            ->label(__('admin/default.labels.images'))
                            ->schema([
                                self::getImageFormField(),

                                self::getSortOrderFormField([
                                    'rules' => ['nullable', 'numeric', 'min:0'],
                                ]),
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
    protected static function createDiscountsTabs(): Tabs\Tab
    {
        return Tabs\Tab::make(__('admin/default.tabs.discounts'))
            ->schema([
                Section::make(__('admin/default.sections.discounts'))
                    ->schema([
                        Repeater::make('discounts')
                            ->label(__('admin/default.labels.discounts'))
                            ->schema([
                                self::getSelectFormField([
                                    'field_name' => 'user_group_id',
                                    'label'      => __('admin/default.labels.user_group'),
                                    'options'    => function () {
                                        return new UserGroup()
                                            ->getActiveUserGroups()
                                            ->mapWithKeys(function (UserGroup $user_group) {
                                                $name = $user_group->name ?: "User Group #$user_group->id";

                                                return [$user_group->id => $name];
                                            });
                                    },
                                    'rules'      => ['required', 'numeric', Rule::exists('user_groups', 'id')],
                                    'required'   => true,
                                ]),

                                self::getNumericFormField([
                                    'field_name' => 'quantity',
                                    'label'      => __('admin/default.labels.discount_quantity'),
                                    'rules'      => ['required', 'numeric', 'min:1'],
                                    'min_value'  => 1,
                                    'default'    => 1,
                                ]),

                                self::getNumericFormField([
                                    'field_name' => 'priority',
                                    'label'      => __('admin/default.labels.priority'),
                                    'rules'      => ['required', 'numeric', 'min:1'],
                                    'min_value'  => 1,
                                    'default'    => 1,
                                ]),

                                self::getPriceFormField([
                                    'field_name' => 'price',
                                    'label'      => __('admin/default.labels.discount_price'),
                                    'rules'      => ['nullable', 'numeric', 'min:0'],
                                ]),

                                self::getDateStartFormField(),

                                self::getDateEndFormField(),
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
    protected static function createAttributesTabs(Collection $active_languages): Tabs\Tab
    {
        $current_language_id = self::tryGetCurrentLanguageIdFromActiveLangs($active_languages);

        return Tabs\Tab::make(__('admin/default.tabs.attributes'))
            ->schema([
                Section::make(__('admin/default.sections.attributes'))
                    ->schema([
                        Repeater::make('attributes')
                            ->label(__('admin/default.labels.attributes'))
                            ->schema([
                                self::getSelectFormField([
                                    'field_name'                => 'attribute_id',
                                    'label'                     => __('admin/default.labels.attribute'),
                                    'options'                   => function () use ($current_language_id) {
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
                                    },
                                    'get_option_label_using_cb' => function ($attribute_id) use ($current_language_id) {
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
                                    },
                                    'after_state_updated_cb'    => function ($state, $set, $get) {
                                        // Auto-validate uniqueness on change
                                    },
                                    'rules'                     => ['required', 'numeric', Rule::exists('attributes', 'id')],
                                    'live'                      => true,
                                    'preload'                   => true,
                                    'required'                  => true,
                                ]),

                                self::getSelectFormField([
                                    'field_name'             => 'language_id',
                                    'label'                  => __('admin/default.labels.language'),
                                    'options'                => $active_languages->pluck('name', 'id'),
                                    'after_state_updated_cb' => function ($state, $set, $get) {
                                        // Auto-validate uniqueness on change
                                    },
                                    'rules'                  => ['required', 'numeric', Rule::exists('attributes', 'id')],
                                    'default'                => $current_language_id,
                                    'live'                   => true,
                                    'preload'                => true,
                                    'required'               => true,
                                ]),

                                self::getTextFormField([
                                    'field_name' => 'text',
                                    'label'      => __('admin/default.labels.attribute_text'),
                                    'required'   => true,
                                ]),
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
}
