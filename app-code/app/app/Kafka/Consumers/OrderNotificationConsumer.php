<?php

declare(strict_types=1);

namespace App\Kafka\Consumers;

use App\Enums\Order\OrderNotificationChannelEnum;
use App\Enums\Order\OrderNotificationDeliveryStatusEnum;
use App\Mail\OrderNotificationMail;
use App\Models\Orders\OrderNotificationDelivery;
use App\Models\Orders\OrderNotificationEvent;
use App\Services\Order\OrderNotificationPayloadBuilder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use RuntimeException;
use Throwable;

final class OrderNotificationConsumer extends Consumer
{
    public function __construct(private readonly OrderNotificationPayloadBuilder $payload_builder)
    {
    }

    /**
     * @param ConsumerMessage $message
     * @param MessageConsumer $consumer
     *
     * @return void
     */
    public function handle(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $body = $message->getBody();
        $event_id = is_array($body) ? string_value(Arr::get($body, 'event_id', '')) : '';
        $event = OrderNotificationEvent::query()->where('event_id', $event_id)->first();

        if (! $event instanceof OrderNotificationEvent) {
            Log::channel('stack')->critical('[OrderNotificationConsumer] event not found', ['event_id' => $event_id]);

            return;
        }

        foreach (OrderNotificationChannelEnum::cases() as $channel) {
            $this->deliver($event, $channel);
        }
    }

    /**
     * @param OrderNotificationEvent       $event
     * @param OrderNotificationChannelEnum $channel
     *
     * @return void
     */
    private function deliver(OrderNotificationEvent $event, OrderNotificationChannelEnum $channel): void
    {
        $delivery = OrderNotificationDelivery::query()->firstOrCreate(
            ['order_notification_event_id' => $event->getKey(), 'channel' => $channel],
            ['status' => OrderNotificationDeliveryStatusEnum::Pending],
        );

        if ($delivery->status === OrderNotificationDeliveryStatusEnum::Sent) {
            return;
        }

        try {
            $payload = $event->payload;
            $provider_reference = match ($channel) {
                OrderNotificationChannelEnum::Telegram => $this->sendTelegram($payload),
                OrderNotificationChannelEnum::SalesDrive => $this->sendSalesDrive($payload),
                OrderNotificationChannelEnum::Email => $this->sendEmail($payload),
            };

            $delivery->forceFill([
                'status' => OrderNotificationDeliveryStatusEnum::Sent,
                'attempts' => $delivery->attempts + 1,
                'provider_reference' => $provider_reference,
                'sent_at' => now(),
                'last_error' => null,
            ])->saveQuietly();
        } catch (Throwable $throwable) {
            $delivery->forceFill([
                'status' => OrderNotificationDeliveryStatusEnum::Failed,
                'attempts' => $delivery->attempts + 1,
                'last_error' => $throwable->getMessage(),
            ])->saveQuietly();
            Log::channel('stack')->critical('[OrderNotificationConsumer] delivery failed', [
                'event_id' => $event->event_id,
                'channel' => $channel->value,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string
     * @throws ConnectionException
     * @throws RequestException
     */
    private function sendTelegram(array $payload): string
    {
        $token = config('order-notifications.telegram.bot_token');
        $chat_id = config('order-notifications.telegram.chat_id');

        if (! is_string($token) || $token === '' || ! is_string($chat_id) || $chat_id === '') {
            Log::channel('stack')->critical('[OrderNotificationConsumer] Telegram credentials are not configured');

            throw new RuntimeException('Telegram order notification credentials are not configured.');
        }

        $response = Http::timeout(15)->post('https://api.telegram.org/bot' . string_value($token) . '/sendMessage', [
            'chat_id' => $chat_id,
            'text' => $this->payload_builder->formatText($payload),
        ])->throw();

        return string_value(data_get($response->json(), 'result.message_id'));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string
     * @throws ConnectionException
     * @throws RequestException
     */
    private function sendSalesDrive(array $payload): string
    {
        $endpoint = config('order-notifications.salesdrive.endpoint');
        $token = config('order-notifications.salesdrive.token');

        if (! is_string($endpoint) || $endpoint === '') {
            Log::channel('stack')->critical('[OrderNotificationConsumer] SalesDrive endpoint is not configured');

            throw new RuntimeException('SalesDrive order notification endpoint is not configured.');
        }

        $response = Http::timeout(20)->withToken(string_value($token))->post(string_value($endpoint), $payload)->throw();

        return string_value($response->header('X-Request-Id'));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string|null
     */
    private function sendEmail(array $payload): ?string
    {
        $email = data_get($payload, 'customer.email');

        if (! is_string($email) || $email === '') {
            return null;
        }

        Mail::mailer(string_value(config('order-notifications.email.mailer', 'log')))
            ->to($email)
            ->send(new OrderNotificationMail($payload));

        return $email;
    }
}
