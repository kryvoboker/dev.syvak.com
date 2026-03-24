<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\Categories;

use App\Filament\Resources\Catalogs\Categories\Categories\CategoryResource;
use App\Filament\Resources\Catalogs\Categories\Categories\RelationManagers\ProductsRelationManager;
use Tests\TestCase;

class CategoryResourceRelationsTest extends TestCase
{
    public function test_category_resource_registers_products_relation_manager(): void
    {
        $relations = CategoryResource::getRelations();

        $this->assertContains(ProductsRelationManager::class, $relations);
    }
}
