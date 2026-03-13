<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\Products;

use App\Filament\Resources\Catalogs\Products\Products\Pages\CreateProduct;
use App\Filament\Resources\Catalogs\Products\Products\Pages\EditProduct;
use Tests\TestCase;

class ProductCategoriesMutationStateTest extends TestCase
{
    public function test_create_page_preserves_normalized_categories_before_unset(): void
    {
        $page = new TestableCreateProductPage();

        $mutated_data = $page->callMutateFormDataBeforeCreate([
            'categories'   => [10, '2', 10, 0, -1, 1],
            'descriptions' => [],
            'images'       => [],
            'discounts'    => [],
            'attributes'   => [],
            'slugs'        => [],
        ]);

        $this->assertArrayNotHasKey('categories', $mutated_data);
        $this->assertSame([1, 2, 10], $page->getCategoryIdsForTest());
    }

    public function test_edit_page_preserves_normalized_categories_before_unset(): void
    {
        $page = new TestableEditProductPage();

        $mutated_data = $page->callMutateFormDataBeforeSave([
            'categories'   => [5, '7', 5, 0, -3, 1],
            'descriptions' => [],
            'images'       => [],
            'discounts'    => [],
            'attributes'   => [],
            'slugs'        => [],
        ]);

        $this->assertArrayNotHasKey('categories', $mutated_data);
        $this->assertSame([1, 5, 7], $page->getCategoryIdsForTest());
    }
}

class TestableCreateProductPage extends CreateProduct
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function callMutateFormDataBeforeCreate(array $data): array
    {
        return $this->mutateFormDataBeforeCreate($data);
    }

    /**
     * @return array<int>
     */
    public function getCategoryIdsForTest(): array
    {
        return $this->category_ids;
    }
}

class TestableEditProductPage extends EditProduct
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function callMutateFormDataBeforeSave(array $data): array
    {
        return $this->mutateFormDataBeforeSave($data);
    }

    /**
     * @return array<int>
     */
    public function getCategoryIdsForTest(): array
    {
        return $this->category_ids;
    }
}
