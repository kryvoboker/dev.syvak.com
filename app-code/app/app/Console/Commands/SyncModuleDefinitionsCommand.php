<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Modules\ModuleDefinitionSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncModuleDefinitionsCommand extends Command
{
    protected $signature = 'app:sync-module-definitions';

    protected $description = 'Synchronize nwidart filesystem modules with database definitions';

    /**
     * @param ModuleDefinitionSyncService $module_definition_sync_service
     *
     * @return int
     * @throws Throwable
     */
    public function handle(ModuleDefinitionSyncService $module_definition_sync_service): int
    {
        $summary = $module_definition_sync_service->sync();

        $this->components->info('Module definitions synchronized successfully.');
        $this->line('Created: ' . $summary['created']);
        $this->line('Updated: ' . $summary['updated']);
        $this->line('Missing: ' . $summary['missing']);

        return self::SUCCESS;
    }
}
