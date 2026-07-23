<?php

declare(strict_types=1);

namespace Modules\FakeModule\Services\Storefront;

class FakeModuleStorefrontService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolveForPlacement(string $placement, ?string $page_type = null): array
    {
        return [
            [
                'instance_id' => 7001,
                'placement' => $placement,
                'page_type' => $page_type,
            ],
            [
                'instance_id' => 7002,
                'placement' => $placement,
                'page_type' => $page_type,
            ],
        ];
    }
}
