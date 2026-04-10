<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\RelationManagers;

use App\Filament\Resources\Catalogs\Products\Products\ProductVariantResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')->sortable(),
                IconColumn::make('is_default')->boolean()->label('Default'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('quantity')->numeric()->sortable(),
                TextColumn::make('price')->numeric(2)->sortable(),
                ImageColumn::make('image'),
                TextColumn::make('sort_order')->numeric()->sortable(),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('admin/catalogs/products/products.actions.create_variant'))
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => ProductVariantResource::getUrl('create', [
                        'product' => (int) data_get($this->getOwnerRecord(), 'id'),
                    ])),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label(__('admin/default.buttons.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record): string => ProductVariantResource::getUrl('edit', [
                        'record'  => $record,
                        'product' => (int) data_get($this->getOwnerRecord(), 'id'),
                    ])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
