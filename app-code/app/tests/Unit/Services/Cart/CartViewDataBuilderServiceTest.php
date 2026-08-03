<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cart;

use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Catalogs\Products\ProductVariantImage;
use App\Services\Cart\CartViewDataBuilderService;
use Illuminate\Database\Eloquent\Collection;
use ReflectionMethod;
use Tests\TestCase;

class CartViewDataBuilderServiceTest extends TestCase
{
    public function test_cart_uses_the_variant_general_image_before_sorted_gallery_images(): void
    {
        $variant = new ProductVariant([
            'image' => 'products/general-image.jpg',
        ]);
        $variant->setRelation('images', new Collection([
            new ProductVariantImage([
                'image' => 'products/gallery-image.jpg',
                'sort_order' => 1,
            ]),
        ]));

        $this->assertSame(
            'products/general-image.jpg',
            $this->resolveVariantImagePath($variant),
        );
    }

    public function test_cart_uses_the_first_sorted_gallery_image_when_general_image_is_empty(): void
    {
        $variant = new ProductVariant([
            'image' => null,
        ]);
        $variant->setRelation('images', new Collection([
            new ProductVariantImage([
                'image' => 'products/first-gallery-image.jpg',
                'sort_order' => 10,
            ]),
            new ProductVariantImage([
                'image' => 'products/second-gallery-image.jpg',
                'sort_order' => 20,
            ]),
        ]));

        $this->assertSame(
            'products/first-gallery-image.jpg',
            $this->resolveVariantImagePath($variant),
        );
    }

    private function resolveVariantImagePath(ProductVariant $variant): ?string
    {
        $service = (new \ReflectionClass(CartViewDataBuilderService::class))
            ->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($service, 'resolveVariantImagePath');

        return $method->invoke($service, $variant);
    }
}
