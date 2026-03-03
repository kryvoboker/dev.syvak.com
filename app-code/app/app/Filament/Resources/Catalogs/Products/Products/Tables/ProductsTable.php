<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Tables;

use App\Filament\Resources\Trait\Filters\BooleanFilterTrait;
use App\Filament\Resources\Trait\Filters\CommonTextFilterTrait;
use App\Filament\Resources\Trait\Filters\NumericFilterTrait;
use App\Filament\Resources\Trait\Filters\SlugFilterTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Filament\Resources\Trait\Tables\BooleanTableTrait;
use App\Filament\Resources\Trait\Tables\CommonTextTableTrait;
use App\Filament\Resources\Trait\Tables\DateTableTrait;
use App\Filament\Resources\Trait\Tables\ImageTableTrait;
use App\Filament\Resources\Trait\Tables\NumericTableTrait;
use App\Filament\Resources\Trait\Tables\SlugTableTrait;
use App\Models\Catalogs\Products\Product;
use App\Supports\Services\Currency\ConvertPrice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductsTable
{
    use LanguageTrait, CommonTextTableTrait, BooleanTableTrait,
        DateTableTrait, ImageTableTrait, SlugTableTrait,
        BooleanFilterTrait, CommonTextFilterTrait, SlugFilterTrait,
        NumericFilterTrait, NumericTableTrait;

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
                    'productDescription',
                    'productDiscount',
                    'productImage',
                    'productToAttribute',
                    'categories',
                    'slugs',
                ]);
            })
            ->columns([
                self::getTextTableField([
                    'field_name'         => 'productDescription.name',
                    'label'              => __('admin/default.columns.name'),
                    'searchable'         => ['name'],
                    'get_state_using_cb' => function (Product $record) use ($current_language_id) {
                        if (($returned_value = self::validateLanguageIdIsNotNull($current_language_id)) !== null) {
                            return $returned_value;
                        }

                        $description = $record->productDescription
                            ->firstWhere('language_id', $current_language_id);

                        if (!$description) {
                            $description = $record->productDescription->first();
                        }

                        return $description?->name ?? '-';
                    },
                ]),

                self::getTextTableField([
                    'field_name' => 'model',
                    'label'      => __('admin/default.columns.model'),
                ]),

                self::getTextTableField([
                    'field_name' => 'sku',
                    'label'      => __('admin/default.columns.sku'),
                ]),

                self::getTextTableField([
                    'field_name'                   => 'ean',
                    'label'                        => __('admin/default.columns.ean'),
                    'is_toggled_hidden_by_default' => true,
                ]),

                self::getNumericTableField([
                    'field_name' => 'quantity',
                    'label'      => __('admin/default.columns.quantity'),
                ]),

                self::getNumericTableField([
                    'field_name'                   => 'minimum',
                    'label'                        => __('admin/default.columns.minimum'),
                    'is_toggled_hidden_by_default' => true,
                ]),

                self::getImageTableField(),

                self::getTextTableField([
                    'field_name'         => 'price',
                    'label'              => __('admin/default.columns.price'),
                    'html'               => true,
                    'get_state_using_cb' => function (Product $record) {
                        $discount = new Product()->getLastActualAndLastModifiedDiscountFromModel($record);

                        $currency      = config('app.currency.default_currency_code');
                        $exchange_rate = (float)config('app.currency.default_exchange_rate');

                        $convert_price = app(ConvertPrice::class);

                        if ($discount === null || $discount->price <= 0) {
                            return $convert_price->format($record->price, $currency, $exchange_rate);
                        }

                        $old_price = $convert_price->format($record->price, $currency, $exchange_rate);
                        $new_price = $convert_price->format($discount->price, $currency, $exchange_rate);

                        return '<span style="font-size: 1rem; text-decoration: line-through; color: rgb(156,163,175);"><del>' . $old_price . '</del></span><br>' .
                            '<span style="font-size: 1.3rem; color: rgb(239,68,68); font-weight: 600;">' . $new_price . '</span>';
                    }
                ]),

                self::getNumericTableField([
                    'field_name'                   => 'viewed',
                    'label'                        => __('admin/default.columns.viewed'),
                    'is_toggled_hidden_by_default' => true,
                ]),

                self::getDateAvailableTableField(),

                self::getDateAddedTableField(),

                self::getIsActiveTableField(),

                self::getSlugTableField([
                    'field_name'         => 'slugs.slug',
                    'searchable'         => ['slug'],
                    'get_state_using_cb' => function (Product $product) use ($current_language_id) {
                        if ($current_language_id === null) {
                            return '-';
                        }

                        $slug = $product->slugs
                            ->firstWhere('language_id', $current_language_id)?->slug;

                        return $slug ?? '-';
                    },
                ]),

                self::getCreatedAtTableField(),

                self::getUpdatedAtTableField(),
            ])
            ->filters([
                self::getIsActiveFilterField(),

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
                            'productDescription',
                            function (Builder $query) use ($search) {
                                return $query->where('name', 'LIKE', "%$search%");
                            }
                        );
                    },
                ]),

                self::getTextFilterField([
                    'filter_label' => __('admin/default.filters.model'),
                    'field_name'   => 'model',
                    'placeholder'  => __('admin/default.placeholders.model'),
                ]),

                self::getTextFilterField([
                    'filter_label' => __('admin/default.filters.sku'),
                    'field_name'   => 'sku',
                    'placeholder'  => __('admin/default.placeholders.sku'),
                ]),

                self::getTextFilterField([
                    'filter_label' => __('admin/default.filters.ean'),
                    'field_name'   => 'ean',
                    'placeholder'  => __('admin/default.placeholders.ean'),
                ]),

                self::getNumericFilterField([
                    'filter_label' => __('admin/default.filters.price'),
                    'field_name'   => 'price',
                    'placeholder'  => __('admin/default.placeholders.price'),
                ]),

                self::getNumericFilterField([
                    'min_length'   => 0,
                    'filter_label' => __('admin/default.filters.price'),
                    'field_name'   => 'price',
                    'placeholder'  => __('admin/default.placeholders.price'),
                ]),

                self::getNumericFilterField([
                    'min_length'   => 0,
                    'filter_label' => __('admin/default.filters.quantity'),
                    'field_name'   => 'quantity',
                    'placeholder'  => __('admin/default.placeholders.quantity'),
                ]),

                self::getTextFilterField([
                    'filter_label' => __('admin/default.filters.category'),
                    'field_name'   => 'category',
                    'placeholder'  => __('admin/default.placeholders.category'),
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
