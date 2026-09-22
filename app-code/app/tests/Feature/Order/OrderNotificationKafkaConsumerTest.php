<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Kafka\Consumers\OrderNotificationConsumer;
use App\Services\Order\OrderNotificationPublisher;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class OrderNotificationKafkaConsumerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function it_forwards_a_valid_kafka_envelope_to_rabbitmq(): void
    {
        $publisher = Mockery::mock(OrderNotificationPublisher::class);
        $publisher->expects('publish')->once()->with([
            'event_id' => '01event',
            'schema_version' => 1,
            'order_id' => 15,
            'payload' => ['order' => ['number' => '01order']],
        ]);
        $this->app->instance(OrderNotificationPublisher::class, $publisher);

        /** @var ConsumerMessage&Mockery\MockInterface $message */
        $message = Mockery::mock(ConsumerMessage::class);
        $message->expects('getBody')->once()->andReturn([
            'event_id' => '01event',
            'schema_version' => 1,
            'order_id' => 15,
            'payload' => ['order' => ['number' => '01order']],
        ]);

        /** @var MessageConsumer&Mockery\MockInterface $consumer */
        $consumer = Mockery::mock(MessageConsumer::class);

        app(OrderNotificationConsumer::class)->handle($message, $consumer);
    }

    #[Test]
    public function it_rejects_an_invalid_kafka_envelope(): void
    {
        $publisher = Mockery::mock(OrderNotificationPublisher::class);
        $publisher->expects('publish')->never();
        $this->app->instance(OrderNotificationPublisher::class, $publisher);

        /** @var ConsumerMessage&Mockery\MockInterface $message */
        $message = Mockery::mock(ConsumerMessage::class);
        $message->expects('getBody')->once()->andReturn([
            'event_id' => '',
            'order_id' => 0,
            'payload' => null,
        ]);

        $this->expectException(RuntimeException::class);

        /** @var MessageConsumer&Mockery\MockInterface $consumer */
        $consumer = Mockery::mock(MessageConsumer::class);

        app(OrderNotificationConsumer::class)->handle($message, $consumer);
    }
}
