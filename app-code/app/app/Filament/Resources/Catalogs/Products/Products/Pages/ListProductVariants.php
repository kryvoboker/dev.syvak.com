<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Catalogs\Products\Products\ProductVariantResource;
use App\Models\Catalogs\Products\Product;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Locked;
use LogicException;

class ListProductVariants extends ListRecords
{
    protected static string $resource = ProductVariantResource::class;

    #[Locked]
    public ?int $product_id = null;

    public function mount(): void
    {
        $this->product_id = request()->integer('product');

        abort_if($this->product_id === null || $this->product_id < 1, 404);

        parent::mount();
    }

    public function table(Table $table): Table
    {
        return $table
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
                Action::make('back_to_product')
                    ->label(__('admin/catalogs/products/products.actions.back_to_product'))
                    ->url(fn (): string => ProductResource::getUrl('edit', ['record' => $this->getProductRecord()])),
                CreateAction::make()
                    ->url(fn (): string => ProductVariantResource::getUrl('create', ['product' => $this->product_id])),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label(__('admin/default.buttons.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record): string => ProductVariantResource::getUrl('edit', [
                        'record'  => $record,
                        'product' => $this->product_id,
                    ])),
                DeleteAction::make(),
            ]);
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return ProductVariantResource::getEloquentQuery()
            ->where('product_id', $this->product_id)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getTitle(): string
    {
        return __('admin/catalogs/products/products.pages.variants');
    }

    public function getHeading(): ?string
    {
        return $this->getTitle();
    }

    private function getProductRecord(): Product
    {
        $product = Product::query()->find($this->product_id);

        if (! $product instanceof Product) {
            throw new LogicException('Product record is not initialized.');
        }

        return $product;
    }
}
