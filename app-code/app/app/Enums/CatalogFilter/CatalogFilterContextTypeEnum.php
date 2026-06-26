<?php

declare(strict_types=1);

namespace App\Enums\CatalogFilter;

enum CatalogFilterContextTypeEnum: string
{
    case Category = 'category';
    case Catalog = 'catalog';
    case Search = 'search';
}
