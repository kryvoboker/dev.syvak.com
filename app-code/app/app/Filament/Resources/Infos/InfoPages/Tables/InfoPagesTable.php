<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Tables;

use App\Filament\Resources\Trait\Filters\BooleanFilterTrait;
use App\Filament\Resources\Trait\Filters\CommonTextFilterTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Filament\Resources\Trait\Tables\BooleanTableTrait;
use App\Filament\Resources\Trait\Tables\CommonTextTableTrait;
use App\Filament\Resources\Trait\Tables\DateTableTrait;
use App\Filament\Resources\Trait\Tables\NumericTableTrait;
use App\Filament\Resources\Trait\Tables\SlugTableTrait;
use App\Models\Infos\InfoPage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class InfoPagesTable
{
    use LanguageTrait, CommonTextTableTrait, NumericTableTrait,
        BooleanTableTrait, DateTableTrait, SlugTableTrait,
        BooleanFilterTrait, CommonTextFilterTrait;

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
                    'infoPageDescription',
                    'slugs',
                ]);
            })
            ->columns([
                self::getTextTableField([
                    'filed_name'         => 'infoPageDescription.title',
                    'label'              => __('admin/default.columns.title'),
                    'searchable'         => ['title'],
                    'get_state_using_cb' => function (InfoPage $record) use ($current_language_id) {
                        if (($returned_value = self::validateLanguageIdIsNotNull($current_language_id)) !== null) {
                            return $returned_value;
                        }

                        $info_page_description = $record->infoPageDescription()
                            ->firstWhere('language_id', $current_language_id);

                        if ($info_page_description === null) {
                            $info_page_description = $record->infoPageDescription()->first();
                        }

                        return $info_page_description?->title ?? '-';
                    },
                ]),

                TextColumn::make('positions')
                    ->label(__('admin/infos/info_pages.columns.position'))
                    ->sortable()
                    ->searchable(),

                self::getNumericTableField([
                    'sort_order' => 'sort_order',
                    'label'      => __('admin/default.columns.sort_order'),
                ]),

                self::getIsActiveTableField(),

                self::getSlugTableField([
                    'searchable'         => ['slug'],
                    'get_state_using_cb' => function (InfoPage $info_page) use ($current_language_id) {
                        if ($current_language_id === null) {
                            return '-';
                        }

                        $slug = $info_page->slugs
                            ->firstWhere('language_id', $current_language_id)?->slug;

                        return $slug ?? '-';
                    },
                ]),

                self::getIsNoIndexTableField(),

                self::getCreatedAtTableField(),

                self::getUpdatedAtTableField(),
            ])
            ->filters([
                self::getIsActiveFilterField(),

                self::getTextFilterField([
                    'field_name'   => 'title',
                    'filter_label' => __('admin/default.filters.title'),
                    'placeholder'  => __('admin/default.placeholders.title'),
                    'query_cb'     => function (Builder $query, array $data): Builder {
                        $search = $data['title'] ?? null;

                        // Apply validation in query
                        if (str_more_or_equal_length($search, 3) === false) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas(
                            'infoPageDescription',
                            function (Builder $query) use ($search) {
                                return $query->where('title', 'LIKE', "%$search%");
                            }
                        );
                    },
                ]),
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
