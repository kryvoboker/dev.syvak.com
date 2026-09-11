<?php

declare(strict_types=1);

namespace App\Console\Commands\Order;

use App\Services\Order\OrderNotificationDeliveryService;
use App\Services\Order\RabbitMqOrderNotificationConnectionFactory;
use App\Services\Order\RabbitMqOrderNotificationTopology;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

final class ConsumeOrderNotificationRabbitMqCommand extends Command
{
    protected $signature = 'rabbitmq:consume:order-notifications
        {--queue= : Queue name; defaults to the configured order notification queue}';

    protected $description = 'Consume order notification events from RabbitMQ.';

    public function __construct(
        private readonly RabbitMqOrderNotificationConnectionFactory $connection_factory,
        private readonly RabbitMqOrderNotificationTopology $topology,
        private readonly OrderNotificationDeliveryService $delivery_service,
    ) {
        parent::__construct();
    }

    /**
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        $connection = $this->connection_factory->create();
        $channel = $connection->channel();
        $this->topology->declare($channel);
        $queue = string_value($this->option('queue') ?: config('order-notifications.rabbitmq.queue'));

        $channel->basic_qos(0, 1, false);
        $channel->basic_consume(
            $queue,
            'order-notifications-consumer',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($channel): void {
                $this->processMessage($channel, $message);
            },
        );

        Log::channel('daily')->info('[ConsumeOrderNotificationRabbitMqCommand] consumer started', [
            'queue' => $queue,
        ]);

        try {
            while ($channel->is_consuming()) {
                $channel->wait();
            }
        } catch (Throwable $throwable) {
            Log::channel('stack')->critical('[ConsumeOrderNotificationRabbitMqCommand] consumer stopped unexpectedly', [
                'queue' => $queue,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            $channel->close();
            $connection->close();

            return self::FAILURE;
        }

        $channel->close();
        $connection->close();

        return self::SUCCESS;
    }

    private function processMessage(AMQPChannel $channel, AMQPMessage $message): void
    {
        try {
            $envelope = string_keyed_array(json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR));

            $this->delivery_service->process($envelope);
            $message->ack();
        } catch (Throwable $throwable) {
            Log::channel('stack')->critical('[ConsumeOrderNotificationRabbitMqCommand] message processing failed', [
                'delivery_tag' => $message->getDeliveryTag(),
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            $channel->basic_nack($message->getDeliveryTag());
        }
    }
}
