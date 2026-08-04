<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\FilterProductsAction;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterValue;
use Illuminate\Http\Request;
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
