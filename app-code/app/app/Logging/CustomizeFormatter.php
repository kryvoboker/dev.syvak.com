<?php

declare(strict_types=1);

namespace App\Logging;

use DateInvalidTimeZoneException;
use DateTimeZone;
use Illuminate\Log\Logger;

class CustomizeFormatter
{
    /**
     * Customize the given logger instance.
     *
     * @param Logger $logger
     *
     * @return void
     * @throws DateInvalidTimeZoneException
     */
    public function __invoke(Logger $logger): void
    {
        $monolog_logger = $logger->getLogger();

        foreach ($monolog_logger->getHandlers() as $handler) {
            $formatter = $handler->getFormatter();

            if (method_exists($formatter, 'setDateFormat')) {
                $timezone = new DateTimeZone(config('app.timezone'));
                $formatter->setDateFormat('Y-m-d H:i:s');

                // Add processor to set timezone
                $monolog_logger->pushProcessor(function ($record) use ($timezone) {
                    $record['datetime'] = $record['datetime']->setTimezone($timezone);
                    $record['channel']  = config('app.name');

                    return $record;
                });
            }
        }
    }
}
