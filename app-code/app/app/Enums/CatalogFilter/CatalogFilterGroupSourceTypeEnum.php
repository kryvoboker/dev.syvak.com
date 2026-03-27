<?php

declare(strict_types=1);

namespace App\Enums\CatalogFilter;

enum CatalogFilterGroupSourceTypeEnum: string
{
    case Attribute = 'attribute';
    case Price     = 'price';
    case System    = 'system';
}
