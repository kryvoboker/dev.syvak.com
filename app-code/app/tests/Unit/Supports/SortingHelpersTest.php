<?php

declare(strict_types=1);

namespace Tests\Unit\Supports;

use App\Models\PageSettings\PageSetting;
use Tests\TestCase;

class SortingHelpersTest extends TestCase
{
    public function test_disabled_category_sorting_returns_no_sorting_items(): void
    {
        $page_setting = new PageSetting([
            'settings' => [
                'ui' => [
                    'sorting' => [
                        'enabled' => false,
                    ],
                ],
                'items' => [
                    'sorting' => [
                        [
                            'code' => 'default',
                            'is_enabled' => true,
                            'sort_order' => 10,
                            'get' => [
                                'value' => 'default',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue(get_sorting_items($page_setting)->isEmpty());
        $this->assertSame('default', resolve_sort_code($page_setting, 'newest'));
    }

    public function test_default_and_newest_sort_values_are_resolved_as_configured_aliases(): void
    {
        $page_setting = new PageSetting([
            'settings' => [
                'ui' => [
                    'sorting' => [
                        'enabled' => true,
                    ],
                ],
                'items' => [
                    'sorting' => [
                        [
                            'code' => 'default',
                            'is_enabled' => true,
                            'sort_order' => 10,
                            'get' => [
                                'value' => 'default',
                            ],
                        ],
                        [
                            'code' => 'newest',
                            'is_enabled' => true,
                            'sort_order' => 20,
                            'get' => [
                                'value' => 'newest',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('default', resolve_sort_code($page_setting, 'default'));
        $this->assertSame('newest', resolve_sort_code($page_setting, 'newest'));
    }
}
