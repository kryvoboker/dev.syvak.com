<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Schemas;

use App\Filament\Resources\Trait\Forms\MetaTextFormTrait;
use App\Filament\Resources\Trait\Forms\SlugFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Categories\Category;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Throwable;

class CategoryForm
{
    use LanguageTrait;
    use MetaTextFormTrait;
    use SlugFormTrait;

    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getAcriveLanguages();

        return $schema
            ->components([
                Tabs::make('CategoryTabs')
                    ->tabs([
                        self::createGeneralTab($active_languages),
                        self::createTranslationsFormTabs($active_languages),
                        self::createMetaTextsFormTabs($active_languages),
                        self::createImagesTab(),
                        self::createSlugsFormTabs($active_languages),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  Collection<Language>  $active_languages
     */
    protected static function createGeneralTab(Collection $active_languages): Tabs\Tab
    {
        $current_language_id = self::tryGetCurrentLanguageIdFromActiveLangs($active_languages);

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
                            ->helperText(__('admin/default.helpers.parent_category'))
                            ->placeholder(__('admin/default.placeholders.select_parent_category'))
                            ->options(function (?Category $record) use ($current_language_id) {
                                $query = Category::query()
                                    ->with([
                                        'categoryDescription' => fn ($query) => $query->where('language_id', $current_language_id),
                                    ])
                                    ->where('is_active', true)
                                    ->orderBy('sort_order');

                                // Exclude the current category when editing
                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }

                                return $query->get()
                                    ->mapWithKeys(function (Category $category) {
                                        $name = (string) data_get(
                                            $category->categoryDescription->first(),
                                            'name',
                                            "Category #$category->id",
                                        );

                                        return [$category->id => $name];
                                    });
                            })
                            ->searchable()
                            ->nullable()
                            ->preload(false)
                            ->live()
                            ->rules(['nullable', 'numeric', Rule::exists('categories', 'id')]),

                        Toggle::make('is_active')
                            ->label(__('admin/default.labels.is_active'))
                            ->default(true)
                            ->required(),

                        TextInput::make('sort_order')
                            ->label(__('admin/default.labels.sort_order'))
                            ->numeric()
                            ->rules(['numeric', 'min:0'])
                            ->default(1)
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function createImagesTab(): Tabs\Tab
    {
        $admin_image_settings = self::resolveCategoryAdminImageSettings();
        $category_upload_max_size_kb = max(1, (int) data_get($admin_image_settings, 'upload.max_size_kb', (int) config('app.images.category.upload.max_size_kb', 5120)));
        $category_upload_max_size_mb = self::resolveMegabytesFromKilobytes($category_upload_max_size_kb);
        $image_upload_directory = resolve_upload_path_placeholders((string) data_get($admin_image_settings, 'upload.directory', (string) config('app.images.category.image_path', 'images/categories/' . date('Y/m'))));
        $preview_in_page_width = max(1, (int) data_get($admin_image_settings, 'images.preview_in_page.width', (int) config('app.images.category.preview_in_page_in_admin.width', 500)));
        $preview_in_page_height = max(1, (int) data_get($admin_image_settings, 'images.preview_in_page.height', (int) config('app.images.category.preview_in_page_in_admin.height', 500)));

        return Tabs\Tab::make(__('admin/default.tabs.images'))
            ->schema([
                Section::make(__('admin/default.sections.images'))
                    ->schema([
                        FileUpload::make('icon')
                            ->label(__('admin/default.labels.icon'))
                            ->helperText(__('admin/default.helpers.max_upload_size_mb', ['size' => $category_upload_max_size_mb]))
                            ->image()
                            ->directory($image_upload_directory)
                            ->maxSize($category_upload_max_size_kb)
                            ->rules(['nullable', Rule::file()::types(['image/jpeg', 'image/png', 'image/svg+xml']), 'max:' . $category_upload_max_size_kb])
                            ->preserveFilenames()
                            ->imageEditor()
                            ->imageEditorViewportWidth($preview_in_page_width)
                            ->imageEditorViewportHeight($preview_in_page_height)
                            ->imageEditorAspectRatioOptions([
                                '1:1' => '1:1',
                                '4:3' => '4:3',
                                '16:9' => '16:9',
                            ])
                            ->nullable(),

                        FileUpload::make('preview_image')
                            ->label(__('admin/default.labels.image'))
                            ->helperText(__('admin/default.helpers.max_upload_size_mb', ['size' => $category_upload_max_size_mb]))
                            ->image()
                            ->directory($image_upload_directory)
                            ->maxSize($category_upload_max_size_kb)
                            ->rules(['nullable', Rule::file()::types(['image/jpeg', 'image/png']), 'max:' . $category_upload_max_size_kb])
                            ->preserveFilenames()
                            ->imageEditor()
                            ->imageEditorViewportWidth($preview_in_page_width)
                            ->imageEditorViewportHeight($preview_in_page_height)
                            ->imageEditorAspectRatioOptions([
                                '1:1' => '1:1',
                                '4:3' => '4:3',
                                '16:9' => '16:9',
                            ])
                            ->nullable(),
                    ])
                    ->columns(),
            ]);
    }

    private static function resolveMegabytesFromKilobytes(int $kilobytes): string
    {
        return number_format(max(1, $kilobytes) / 1024, 2, '.', '');
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolveCategoryAdminImageSettings(): array
    {
        $settings = [
            'upload' => [
                'max_size_kb' => (int) config('app.images.category.upload.max_size_kb', 5120),
                'directory' => (string) config('app.images.category.image_path', 'images/categories/' . date('Y/m')),
            ],
            'images' => [
                'preview_in_page' => [
                    'width' => (int) config('app.images.category.preview_in_page_in_admin.width', 500),
                    'height' => (int) config('app.images.category.preview_in_page_in_admin.height', 500),
                ],
            ],
        ];

        try {
            $settings = array_replace_recursive(
                $settings,
                app(PageSettingsBootstrapService::class)->getCategoryAdminImageSettings(),
            );
        } catch (Throwable) {
            // Keep config fallback when page settings are not available.
        }

        return $settings;
    }
}
