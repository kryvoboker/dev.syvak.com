<?php

declare(strict_types=1);

namespace App\Filament\Resources\Marketing\PromoCodes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromoCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin/marketing/promo_codes.labels.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('admin/marketing/promo_codes.labels.code'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('promo_type')
                    ->label(__('admin/marketing/promo_codes.labels.promo_type'))
                    ->badge(),
                TextColumn::make('starts_at')
                    ->label(__('admin/marketing/promo_codes.labels.starts_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('admin/marketing/promo_codes.labels.ends_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('usages_count')
                    ->counts('usages')
                    ->label(__('admin/marketing/promo_codes.labels.usage_count'))
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label(__('admin/marketing/promo_codes.labels.is_active')),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/marketing/promo_codes.labels.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
