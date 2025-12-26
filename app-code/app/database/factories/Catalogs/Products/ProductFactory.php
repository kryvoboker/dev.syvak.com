<?php

declare(strict_types=1);

namespace Database\Factories\Catalogs\Products;

use App\Models\Catalogs\Products\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array
     */
    public function definition(): array
    {
        $sku = (string)$this->faker->unique()->numberBetween(10000, 99999);

        return [
            'model'          => $sku,
            'sku'            => $sku,
            'ean'            => $this->faker->optional()->ean13(),
            'quantity'       => 0,
            'minimum'        => 1,
            'image'          => null,
            'price'          => $this->faker->randomFloat(2, 100, 5000),
            'viewed'         => 0,
            'is_active'      => true,
            'date_available' => now(config('app.timezone')),
            'date_added'     => now(config('app.timezone')),
        ];
    }
}
