<?php

declare(strict_types=1);

namespace App\Enums\CatalogFilter;

enum CatalogFilterIndexStatusEnum: string
{
    case Ok      = 'ok';
    case Warning = 'warning';
    case Failed  = 'failed';
    case Running = 'running';
}
