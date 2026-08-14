<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs;

use Tests\TestCase;

class CategoryProductsListViewTest extends TestCase
{
    public function test_product_card_contains_default_variant_id_for_regular_cart_addition(): void
    {
        $html = view('storefront.pages.partials.category.products-list', [
            'products' => [
                [
                    'variant_id' => 42,
                    'name' => 'Test product',
                    'url' => '#',
                    'image_data' => [
                        'urls' => [
                            'original_thumb' => '',
                            'thumb_1x' => '',
                        ],
                        'width' => 420,
                        'height' => 420,
                    ],
                    'price' => [
                        'formatted' => '100 грн',
                        'discount_value' => null,
                        'discount_formatted' => null,
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('data-add-to-cart="42"', $html);
        $this->assertStringContainsString(
            'aria-label="' . __('storefront/default.aria_labels.add_product_to_cart') . '"',
            $html,
        );
    }
}
