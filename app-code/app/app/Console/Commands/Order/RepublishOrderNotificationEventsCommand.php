<?php

declare(strict_types=1);

namespace App\Console\Commands\Order;

use App\Enums\Order\OrderNotificationEventStatusEnum;
use App\Jobs\PublishOrderNotificationEventJob;
use App\Models\Orders\OrderNotificationEvent;
use Illuminate\Console\Command;

final class RepublishOrderNotificationEventsCommand extends Command
{
    protected $signature = 'orders:notifications:republish {--limit=100 : Maximum number of events to republish}';

    protected $description = 'Republish pending or failed order notification events to Kafka.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $events = OrderNotificationEvent::query()
            ->whereIn('status', [
                OrderNotificationEventStatusEnum::Pending,
                OrderNotificationEventStatusEnum::Failed,
            ])
            ->oldest('id')
            ->limit($limit)
            ->get(['id']);

        foreach ($events as $event) {
            PublishOrderNotificationEventJob::dispatch(integer_value($event->getKey()));
        }

        $this->info(sprintf('Queued %d order notification event(s).', $events->count()));

        return self::SUCCESS;
    }
}
