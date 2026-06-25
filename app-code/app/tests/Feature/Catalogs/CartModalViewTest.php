<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs;

use Tests\TestCase;

class CartModalViewTest extends TestCase
{
    public function testModalItemsRenderLineTotalForEachCartItem(): void
    {
        $cart_data = $this->makeCartData();

        $html = view('catalog.partials.cart.modal-items', [
            'cart_data' => $cart_data,
            'cart_mode' => 'regular',
        ])->render();

        $this->assertStringContainsString('30 грн', $html);
        $this->assertStringNotContainsString('15 грн', $html);
    }

    public function testCartPageContentKeepsUnitPriceForEachCartItem(): void
    {
        $cart_data = $this->makeCartData();

        $html = view('catalog.partials.cart.page-content', [
            'cart_data' => $cart_data,
        ])->render();

        $this->assertStringContainsString('15 грн', $html);
        $this->assertStringContainsString('30 грн', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function makeCartData(): array
    {
        return [
            'is_empty' => false,
            'items_count' => 1,
            'items' => [
                [
                    'cart_id' => 1,
                    'variant_id' => 11,
                    'product_id' => 101,
                    'name' => 'Test product',
                    'url' => '#',
                    'sku' => 'SKU-001',
                    'quantity' => 2,
                    'minimum_quantity' => 1,
                    'available_quantity' => 10,
                    'unit_price_formatted' => '15 грн',
                    'line_total_formatted' => '30 грн',
                    'attributes' => [],
                    'image_data' => [
                        'urls' => [
                            'original_thumb' => '',
                            'thumb_1x' => '',
                        ],
                        'width' => 220,
                        'height' => 220,
                    ],
                ],
            ],
            'first_item' => [
                'cart_id' => 1,
                'variant_id' => 11,
                'product_id' => 101,
                'name' => 'Test product',
                'url' => '#',
                'sku' => 'SKU-001',
                'quantity' => 2,
                'minimum_quantity' => 1,
                'available_quantity' => 10,
                'unit_price_formatted' => '15 грн',
                'line_total_formatted' => '30 грн',
                'attributes' => [],
                'image_data' => [
                    'urls' => [
                        'original_thumb' => '',
                        'thumb_1x' => '',
                    ],
                    'width' => 220,
                    'height' => 220,
                ],
            ],
            'hidden_items' => [],
            'totals' => [
                'lines' => [
                    [
                        'code' => 'items_subtotal',
                        'label' => 'Subtotal',
                        'amount' => 30,
                        'formatted' => '30 грн',
                        'is_visible' => true,
                        'include_in_grand_total' => true,
                    ],
                ],
                'grand_total' => 30,
                'grand_total_formatted' => '30 грн',
            ],
        ];
    }
}
