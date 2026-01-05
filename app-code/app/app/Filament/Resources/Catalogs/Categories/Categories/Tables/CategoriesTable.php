<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Tables;

use App\Filament\Resources\Trait\Forms\SortOrderFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Filament\Resources\Trait\Tables\BooleanTableTrait;
use App\Filament\Resources\Trait\Tables\CommonTextTableTrait;
use App\Filament\Resources\Trait\Tables\DateTableTrait;
use App\Filament\Resources\Trait\Tables\ImageTableTrait;
use App\Filament\Resources\Trait\Tables\SlugTableTrait;
use App\Models\Catalogs\Categories\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoriesTable
{
    use LanguageTrait, CommonTextTableTrait, SortOrderFormTrait, DateTableTrait, BooleanTableTrait, ImageTableTrait, SlugTableTrait;

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
                self::getNameTableField([
                    'filed_name'         => 'categoryDescription.name',
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

                self::getSortOrderFormField(),

                self::getIsActiveTableField(),

                self::getSlugTableField([
                    'filed_name'         => 'slugs.slug',
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
                Filter::make('name')
                    ->label(__('admin/default.filters.name'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin/default.filters.name'))
                            ->placeholder(__('admin/default.placeholders.name'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear empty input to avoid filtering by empty value
                                if ($state === null || Str::length(Str::trim($state)) < 3) {
                                    $set('name', null);
                                }
                            })
                    ])
                    ->query(function (Builder $query, array $data) {
                        $search = $data['name'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas(
                            'categoryDescription',
                            function (Builder $query) use ($search) {
                                return $query->where('name', 'LIKE', "%$search%");
                            }
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['name'] ?? null;

                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.name') . ': ' . Str::trim($search);
                    }),

                Filter::make('slugs')
                    ->label(__('admin/default.filters.slug'))
                    ->schema([
                        TextInput::make('slugs')
                            ->label(__('admin/default.filters.slug'))
                            ->placeholder(__('admin/default.placeholders.slug'))
                            ->minLength(3)
                            ->maxLength(500)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear empty input to avoid filtering by empty value
                                if ($state === null || Str::length(Str::trim($state)) < 3) {
                                    $set('slugs', null);
                                }
                            })
                    ])
                    ->query(function (Builder $query, array $data) {
                        $search = $data['slugs'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas(
                            'slugs',
                            function (Builder $query) use ($search) {
                                return $query
                                    ->whereLike('slug', "$search%");
                            }
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['slugs'] ?? null;

                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.slug') . ': ' . Str::trim($search);
                    }),
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
