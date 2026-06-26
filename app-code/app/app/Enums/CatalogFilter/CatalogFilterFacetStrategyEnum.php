<?php

declare(strict_types=1);

namespace App\Enums\CatalogFilter;

enum CatalogFilterFacetStrategyEnum: string
{
    case AllResults = 'all_results';
    case SelfExcluding = 'self_excluding';
}
