<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\RelationManagers;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\Catalogs\Products\Product;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsRelationManager extends RelationManager
{
    use LanguageTrait;

    protected static string $relationship = 'products';

    public function table(Table $table): Table
    {
        $current_language_id = self::getCurrentLanguageId();

        return $table
            ->heading(__('admin/default.labels.products'))
            ->recordTitleAttribute('model')
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->with([
                    'productDescription',
                    'defaultVariant',
                    'slugs',
                ]);
            })
            ->columns([
                TextColumn::make('productDescription.name')
                    ->label(__('admin/default.columns.name'))
                    ->searchable(['name'])
                    ->sortable()
                    ->limit(60)
                    ->getStateUsing(function (Product $record) use ($current_language_id): string {
                        if (($returned_value = self::validateLanguageIdIsNotNull($current_language_id)) !== null) {
                            return $returned_value;
                        }

                        $description = $record->productDescription
                            ->firstWhere('language_id', $current_language_id);

                        if (! $description) {
                            $description = $record->productDescription->first();
                        }

                        if ($description === null) {
                            return '-';
                        }

                        return $description->name ?? '-';
                    }),

                TextColumn::make('model')
                    ->label(__('admin/default.columns.model'))
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                TextColumn::make('sku')
                    ->label(__('admin/default.columns.sku'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('quantity')
                    ->label(__('admin/default.columns.quantity'))
                    ->sortable()
                    ->getStateUsing(function (Product $record): int {
                        return (int) ($record->defaultVariant->quantity ?? 0);
                    }),

                IconColumn::make('is_active')
                    ->label(__('admin/default.labels.is_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('date_added')
                    ->label(__('admin/default.columns.date_added'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('edit_product')
                    ->label(__('admin/default.buttons.edit'))
                    ->icon(Heroicon::PencilSquare)
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ]);
    }
}
