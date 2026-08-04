<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers\Pages;

use App\Http\Controllers\Pages\CategoryController;
use ReflectionMethod;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    public function test_sort_option_url_resets_page_and_preserves_active_filters(): void
    {
        $url = $this->invokePrivateMethod(
            'buildSortOptionUrl',
            '/en/category/t-shirts',
            [
                'page' => 10,
                'sort' => 'default',
                'attributes' => [
                    'color' => ['black'],
                ],
            ],
            'sort',
            'price-desc',
            ['sort'],
        );

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertArrayNotHasKey('page', $query);
        $this->assertSame('price-desc', $query['sort']);
        $this->assertSame(['black'], $query['attributes']['color']);
    }

    private function invokePrivateMethod(string $method_name, mixed ...$arguments): mixed
    {
        $controller = (new \ReflectionClass(CategoryController::class))
            ->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($controller, $method_name);

        return $method->invoke($controller, ...$arguments);
    }
}
