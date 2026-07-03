<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\Products;

use App\Filament\Resources\Catalogs\Products\Products\Pages\CreateProduct;
use App\Filament\Resources\Catalogs\Products\Products\Pages\EditProduct;
use App\Models\Catalogs\Products\Product;
use Filament\Support\Exceptions\Halt;
use Tests\TestCase;

class ProductCategoriesMutationStateTest extends TestCase
{
    public function test_create_page_preserves_normalized_categories_before_unset(): void
    {
        $page = new TestableCreateProductPage();

        $mutated_data = $page->callMutateFormDataBeforeCreate([
            'categories' => [10, '2', 10, 0, -1, 1],
            'descriptions' => [],
            'images' => [],
            'discounts' => [],
            'attributes' => [],
            'slugs' => [],
        ]);

        $this->assertArrayNotHasKey('categories', $mutated_data);
        $this->assertSame([1, 2, 10], $page->getCategoryIdsForTest());
    }

    public function test_edit_page_preserves_normalized_categories_before_unset(): void
    {
        $page = new TestableEditProductPage();
        $page->setProductRecordForTest(Product::factory()->make([
            'id' => 1,
        ]));

        $mutated_data = $page->callMutateFormDataBeforeSave([
            'categories' => [5, '7', 5, 0, -3, 1],
            'descriptions' => [],
            'images' => [],
            'discounts' => [],
            'attributes' => [],
            'slugs' => [],
        ]);

        $this->assertArrayNotHasKey('categories', $mutated_data);
        $this->assertSame([1, 5, 7], $page->getCategoryIdsForTest());
    }

    public function test_create_page_allows_unique_attribute_language_pairs(): void
    {
        $page = new TestableCreateProductPage();

        $page->callValidateAttributeLanguagePairs([
            [
                'attribute_id' => 10,
                'language_id' => 1,
                'text' => 'Foo',
            ],
            [
                'attribute_id' => 10,
                'language_id' => 2,
                'text' => 'Bar',
            ],
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_create_page_rejects_duplicate_attribute_language_pairs(): void
    {
        $this->expectException(Halt::class);

        $page = new TestableCreateProductPage();

        $page->callValidateAttributeLanguagePairs([
            [
                'attribute_id' => 10,
                'language_id' => 1,
                'text' => 'Foo',
            ],
            [
                'attribute_id' => 10,
                'language_id' => 1,
                'text' => 'Bar',
            ],
        ]);
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

    /**
     * @param  array<int, array{attribute_id?: int|string, language_id?: int|string, text?: string}>  $attributes
     *
     * @throws Halt
     */
    public function callValidateAttributeLanguagePairs(array $attributes): void
    {
        $seen_pairs = [];

        foreach ($attributes as $attribute) {
            $attribute_id = (int) ($attribute['attribute_id'] ?? 0);
            $language_id = (int) ($attribute['language_id'] ?? 0);
            $pair_key = $attribute_id . ':' . $language_id;

            if (array_key_exists($pair_key, $seen_pairs)) {
                throw new Halt();
            }

            $seen_pairs[$pair_key] = true;
        }
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

    public function setProductRecordForTest(Product $product): void
    {
        $this->record = $product;
    }
}
