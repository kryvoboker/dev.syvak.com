<?php

declare(strict_types=1);

namespace App\Enums\CatalogFilter;

enum CatalogFilterValueTypeEnum: string
{
    case String = 'string';
    case Integer = 'int';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Range = 'range';
}
