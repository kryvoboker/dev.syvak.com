<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Catalogs\Products\Products\ProductVariantResource;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Livewire\Attributes\Locked;
use LogicException;

class EditProductVariant extends EditRecord
{
    protected static string $resource = ProductVariantResource::class;

    #[Locked]
    public ?int $product_id = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $query_product_id  = request()->integer('product');
        $record_product_id = (int) data_get($this->getRecord(), 'product_id');

        if ($query_product_id !== null && $query_product_id > 0 && $query_product_id !== $record_product_id) {
            abort(404);
        }

        $this->product_id = $record_product_id;
    }

    public function form(Schema $schema): Schema
    {
        return ProductVariantResource::form($schema);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['product_id'] = $this->product_id;

        if ((bool) ($data['is_default'] ?? false)) {
            ProductVariant::query()
                ->where('product_id', $this->product_id)
                ->where('id', '!=', (int) data_get($this->getRecord(), 'id'))
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(ProductVariantResource::getUrl('index', ['product' => $this->product_id])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ProductVariantResource::getUrl('index', ['product' => $this->product_id]);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->url(ProductResource::getUrl('edit', ['record' => $this->getProductRecord()]));
    }

    public function getTitle(): string
    {
        return __('admin/catalogs/products/products.pages.edit_variant');
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
