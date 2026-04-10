<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Pages;

use App\Filament\Resources\Catalogs\Products\Products\ProductResource;
use App\Filament\Resources\Catalogs\Products\Products\ProductVariantResource;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Livewire\Attributes\Locked;
use LogicException;

class CreateProductVariant extends CreateRecord
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

    public function form(Schema $schema): Schema
    {
        return ProductVariantResource::form($schema);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['product_id'] = $this->product_id;

        if ((bool) ($data['is_default'] ?? false)) {
            ProductVariant::query()
                ->where('product_id', $this->product_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return $data;
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
        return __('admin/catalogs/products/products.pages.create_variant');
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
