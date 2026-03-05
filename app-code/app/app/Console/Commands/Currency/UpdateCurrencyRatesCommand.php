<?php

declare(strict_types=1);

namespace App\Console\Commands\Currency;

use App\Services\Currency\UpdateRatesService;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class UpdateCurrencyRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-currency-rates-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update app currency exchange rates from external source';

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $error_message = app(UpdateRatesService::class)->handle();

        if ($error_message) {
            $this->error($error_message);

            return CommandAlias::FAILURE;
        }

        $this->info('Currency exchange rates updated successfully.');

        return CommandAlias::SUCCESS;
    }
}
