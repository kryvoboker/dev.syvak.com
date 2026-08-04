<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\FilterProductsAction;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Models\Catalogs\CatalogFilter\CatalogFilterValue;
use App\Models\Catalogs\Products\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class FilterProductsActionTest extends TestCase
{
    public function test_removing_an_attribute_filter_resets_the_page_parameter(): void
    {
        $this->setCurrentRequest('/en/category/t-shirts?page=10&color=black,white&sort=price-desc');

        $group = new CatalogFilterGroup([
            'get_key' => 'color',
        ]);
        $value = new CatalogFilterValue([
            'code' => 'black',
        ]);

        $cancel_link = $this->invokePrivateMethod(
            'buildCancelLinkForCheckedValue',
            $group,
            $value,
        );

        $this->assertSame(
            'http://localhost/en/category/t-shirts?color=white&sort=price-desc',
            $cancel_link,
        );
    }

    public function test_removing_a_price_filter_resets_the_page_parameter(): void
    {
        $this->setCurrentRequest('/en/category/t-shirts?page=10&price_from=100&price_to=500&sort=price-asc');

        $cancel_link = $this->invokePrivateMethod(
            'buildCancelLinkForPriceRange',
            'price_from',
            'price_to',
            100,
            500,
        );

        $this->assertSame(
            'http://localhost/en/category/t-shirts?sort=price-asc',
            $cancel_link,
        );
    }

    public function test_attribute_filter_matches_active_non_default_variants(): void
    {
        $filter_set = new CatalogFilterSet([
            'is_attribute_filtering_enabled' => true,
        ]);
        $filter_group = new CatalogFilterGroup();
        $filter_group->setRawAttributes([
            'source_type' => 'attribute',
            'source_id' => 7,
        ]);
        $filter_group->syncOriginal();
        $filter_group->setRelation('values', new Collection([
            new CatalogFilterValue([
                'code' => 'yellow',
                'value_string' => 'Yellow',
            ]),
        ]));

        $query = Product::query();
        $this->invokePrivateMethod(
            'applyAttributeFilters',
            $query,
            $filter_set,
            new Collection([$filter_group]),
            ['attributes' => [7 => ['yellow']]],
        );

        $sql = $query->toSql();

        $this->assertStringContainsString('product_variants', $sql);
        $this->assertStringContainsString('is_active', $sql);
    }

    private function setCurrentRequest(string $url): void
    {
        $this->app->instance('request', Request::create($url));
    }

    private function invokePrivateMethod(string $method_name, mixed ...$arguments): mixed
    {
        $action = (new \ReflectionClass(FilterProductsAction::class))
            ->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($action, $method_name);

        return $method->invoke($action, ...$arguments);
    }
}
