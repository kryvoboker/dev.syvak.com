<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Tables;

use App\Filament\Resources\Trait\Filters\CommonTextFilterTrait;
use App\Filament\Resources\Trait\Filters\SlugFilterTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Filament\Resources\Trait\Tables\BooleanTableTrait;
use App\Filament\Resources\Trait\Tables\CommonTextTableTrait;
use App\Filament\Resources\Trait\Tables\DateTableTrait;
use App\Filament\Resources\Trait\Tables\ImageTableTrait;
use App\Filament\Resources\Trait\Tables\NumericTableTrait;
use App\Filament\Resources\Trait\Tables\SlugTableTrait;
use App\Models\Catalogs\Categories\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoriesTable
{
    use LanguageTrait, CommonTextTableTrait, NumericTableTrait,
        DateTableTrait, BooleanTableTrait, ImageTableTrait,
        SlugTableTrait, CommonTextFilterTrait, SlugFilterTrait;

    /**
     * @param Table $table
     *
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        $current_language_id = self::getCurrentLanguageId();

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Eager load descriptions to avoid N+1 problem
                return $query->with([
                    'categoryDescription',
                    'categoryImage',
                    'slugs',
                ]);
            })
            ->columns([
                self::getTextTableField([
                    'field_name'         => 'categoryDescription.name',
                    'label'              => __('admin/default.columns.name'),
                    'searchable'         => ['name'],
                    'get_state_using_cb' => function (Category $record) use ($current_language_id) {
                        if (($returned_value = self::validateLanguageIdIsNotNull($current_language_id)) !== null) {
                            return $returned_value;
                        }

                        // Try to get description for current locale
                        $description = $record->categoryDescription
                            ->firstWhere('language_id', $current_language_id);

                        // Fallback to first available description
                        if (!$description) {
                            $description = $record->categoryDescription->first();
                        }

                        return $description?->name ?? '-';
                    },
                ]),

                self::getImageTableField([
                    'field_name'           => 'icon',
                    'image_size'           => (int)config('app.images.category.preview_in_list_in_admin.width'),
                    'default_image_url'    => config('app.images.category.no_image'),
                    'extra_img_attributes' => [
                        'decoding' => 'async',
                        'loading'  => 'lazy',
                        'style'    => 'object-fit: contain; background-color: #f9f9f9;',
                    ],
                    'get_state_using_cb'   => function (Category $category) {
                        $category_image = $category->categoryImage()->first();

                        return $category_image?->icon
                            ? Storage::url($category_image->icon)
                            : null;
                    },
                ]),

                self::getNumericTableField([
                    'field_name' => 'sort_order',
                    'label'      => __('admin/default.columns.sort_order'),
                ]),

                self::getIsActiveTableField(),

                self::getSlugTableField([
                    'searchable'         => ['slug'],
                    'get_state_using_cb' => function (Category $category) use ($current_language_id) {
                        if ($current_language_id === null) {
                            return '-';
                        }

                        $slug = $category->slugs
                            ->firstWhere('language_id', $current_language_id)?->slug;

                        return $slug ?? '-';
                    },
                ]),

                self::getCreatedAtTableField(),
            ])
            ->filters([
                self::getTextFilterField([
                    'filter_label' => __('admin/default.filters.name'),
                    'field_name'   => 'name',
                    'placeholder'  => __('admin/default.placeholders.name'),
                    'query_cb'     => function (Builder $query, array $data): Builder {
                        $search = $data['name'] ?? null;

                        // Apply validation in query
                        if (str_more_or_equal_length($search, 3) === false) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas(
                            'categoryDescription',
                            function (Builder $query) use ($search) {
                                return $query->where('name', 'LIKE', "%$search%");
                            }
                        );
                    },
                ]),

                self::getSlugFilterField(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
