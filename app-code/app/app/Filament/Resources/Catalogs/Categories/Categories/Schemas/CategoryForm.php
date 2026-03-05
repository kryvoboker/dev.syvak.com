<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Schemas;

use App\Filament\Resources\Trait\Forms\MetaTextFormTrait;
use App\Filament\Resources\Trait\Forms\SlugFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\Catalogs\Categories\Category;
use App\Models\Settings\Language;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

class CategoryForm
{
    use LanguageTrait, MetaTextFormTrait, SlugFormTrait;

    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getAcriveLanguages();

        return $schema
            ->components([
                Tabs::make('ProductTabs')
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
                                        $name = $category->categoryDescription->first()?->name ?? "Category #$category->id";

                                        return [$category->id => $name];
                                    });
                            })
                            ->searchable()
                            ->nullable()
                            ->preload(false)
                            ->live(false)
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
        return Tabs\Tab::make(__('admin/default.tabs.images'))
            ->schema([
                Section::make(__('admin/default.sections.images'))
                    ->schema([
                        FileUpload::make('icon')
                            ->label(__('admin/default.labels.icon'))
                            ->image()
                            ->directory(config('app.images.category.image_path'))
                            ->maxSize((int) config('app.images.category.upload.max_size_kb'))
                            ->rules(['nullable', Rule::file()::types(['image/jpeg', 'image/png', 'image/svg+xml']), 'max:' . (int) config('app.images.category.upload.max_size_kb')])
                            ->preserveFilenames()
                            ->imageEditor()
                            ->imageEditorViewportWidth((int) config('app.images.category.preview_in_page_in_admin.width'))
                            ->imageEditorViewportHeight((int) config('app.images.category.preview_in_page_in_admin.height'))
                            ->imageEditorAspectRatios([
                                '1:1'  => '1:1',
                                '4:3'  => '4:3',
                                '16:9' => '16:9',
                            ])
                            ->nullable(),

                        FileUpload::make('preview_image')
                            ->label(__('admin/default.labels.image'))
                            ->image()
                            ->directory(config('app.images.category.image_path'))
                            ->maxSize((int) config('app.images.category.upload.max_size_kb'))
                            ->rules(['nullable', Rule::file()::types(['image/jpeg', 'image/png']), 'max:' . (int) config('app.images.category.upload.max_size_kb')])
                            ->preserveFilenames()
                            ->imageEditor()
                            ->imageEditorViewportWidth((int) config('app.images.category.preview_in_page_in_admin.width'))
                            ->imageEditorViewportHeight((int) config('app.images.category.preview_in_page_in_admin.height'))
                            ->imageEditorAspectRatios([
                                '1:1'  => '1:1',
                                '4:3'  => '4:3',
                                '16:9' => '16:9',
                            ])
                            ->nullable(),
                    ])
                    ->columns(),
            ]);
    }
}
