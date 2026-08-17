<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs;

use Tests\TestCase;

class CartModalViewTest extends TestCase
{
    public function test_modal_items_render_line_total_for_each_cart_item(): void
    {
        $cart_data = $this->makeCartData();

        $html = view('storefront.partials.cart.modal-items', [
            'cart_data' => $cart_data,
            'cart_mode' => 'regular',
        ])->render();

        $this->assertStringContainsString('30 грн', $html);
        $this->assertStringNotContainsString('15 грн', $html);
        $this->assertStringContainsString('data-remove-selected-cart-items', $html);
        $this->assertStringContainsString('class="btn btn-text p-0 hidden"', $html);
    }

    public function test_cart_page_content_renders_the_same_interactive_cart_controls(): void
    {
        $cart_data = $this->makeCartData();

        $html = view('storefront.partials.cart.page-content', [
            'cart_data' => $cart_data,
        ])->render();

        $this->assertStringContainsString('data-cart-page-content', $html);
        $this->assertStringContainsString('data-cart-root', $html);
        $this->assertStringContainsString('30 грн', $html);
        $this->assertStringNotContainsString('15 грн', $html);
        $this->assertStringNotContainsString('checkout', $html);
        $this->assertStringContainsString('class="btn btn-text p-0 hidden"', $html);
    }

    public function test_modal_items_render_empty_state_and_hide_checkout_button(): void
    {
        foreach ([
            'uk' => 'Поки що кошик порожній ;(',
            'en' => 'The cart is empty for now ;(',
        ] as $locale => $expected_text) {
            app()->setLocale($locale);

            $html = view('storefront.partials.cart.modal-items', [
                'cart_data' => [
                    'is_empty' => true,
                    'items_count' => 0,
                    'first_item' => null,
                    'hidden_items' => [],
                    'totals' => [],
                ],
                'cart_mode' => 'regular',
            ])->render();

            $this->assertStringContainsString($expected_text, $html);
            $this->assertStringNotContainsString('checkout', $html);
        }
    }

    public function test_cart_modal_accordion_ids_are_unique_for_each_cart_mode(): void
    {
        $cart_data = $this->makeCartData();
        $cart_data['hidden_items'] = [$cart_data['first_item']];

        $regular_html = view('storefront.partials.cart.modal-items', [
            'cart_data' => $cart_data,
            'cart_mode' => 'regular',
        ])->render();
        $fast_order_html = view('storefront.partials.cart.modal-items', [
            'cart_data' => $cart_data,
            'cart_mode' => 'fast_order',
        ])->render();

        $this->assertStringContainsString('id="cart-extra-items-collapse-regular"', $regular_html);
        $this->assertStringContainsString('aria-controls="cart-extra-items-collapse-regular"', $regular_html);
        $this->assertStringContainsString('id="cart-extra-items-collapse-fast_order"', $fast_order_html);
        $this->assertStringContainsString('aria-controls="cart-extra-items-collapse-fast_order"', $fast_order_html);
    }

    public function test_cart_modal_component_keeps_modal_action_buttons_for_empty_cart(): void
    {
        app()->setLocale('uk');

        $html = view('storefront.components.cart.modal', [
            'cart_data' => [
                'is_empty' => true,
                'items_count' => 0,
                'first_item' => null,
                'hidden_items' => [],
                'totals' => [],
            ],
        ])->render();

        $this->assertStringContainsString('Поки що кошик порожній ;(', $html);
        $this->assertStringContainsString(__('storefront/default.cart.buttons.continue_shopping'), $html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'data-overlay="#cart-modal"'));
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
