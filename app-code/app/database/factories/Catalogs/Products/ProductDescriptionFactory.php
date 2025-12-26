<?php

declare(strict_types=1);

namespace Database\Factories\Catalogs\Products;

use App\Models\Catalogs\Products\ProductDescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductDescription>
 */
class ProductDescriptionFactory extends Factory
{
    protected $model = ProductDescription::class;

    /**
     * @return array
     */
    public function definition(): array
    {
        return [
            'product_id'  => null,
            'language_id' => 1,
            'name'        => $this->faker->sentence(4),
            'description' => null,
        ];
    }
}
