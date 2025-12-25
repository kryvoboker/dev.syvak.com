<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Resources\SearchProductResource;
use App\Models\Catalogs\Products\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchProductAction
{
    /**
     * @param string $keyword
     * @param int    $per_page
     *
     * @return AnonymousResourceCollection|SearchProductResource
     */
	public function __invoke(string $keyword, int $per_page): AnonymousResourceCollection|SearchProductResource
    {
        $products = new Product()->search($keyword, $per_page);

        return SearchProductResource::collection($products);
	}
}
