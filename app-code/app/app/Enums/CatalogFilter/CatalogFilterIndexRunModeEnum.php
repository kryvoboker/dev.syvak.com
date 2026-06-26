<?php

declare(strict_types=1);

namespace App\Enums\CatalogFilter;

enum CatalogFilterIndexRunModeEnum: string
{
    case Full = 'full';
    case Incremental = 'incremental';
    case DryRun = 'dry_run';
}
