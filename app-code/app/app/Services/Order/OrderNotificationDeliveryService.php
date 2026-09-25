<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Order\OrderNotificationChannelEnum;
use App\Enums\Order\OrderNotificationDeliveryStatusEnum;
use App\Mail\OrderNotificationMail;
use App\Models\Orders\OrderNotificationDelivery;
use App\Models\Orders\OrderNotificationEvent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

final readonly class OrderNotificationDeliveryService
{
    public function __construct(private OrderNotificationPayloadBuilder $payload_builder)
    {
    }

    /** @param array<string, mixed> $envelope */
    public function process(array $envelope): void
    {
        $event_id = string_value($envelope['event_id'] ?? '');
        $event = OrderNotificationEvent::query()->where('event_id', $event_id)->first();

        if (! $event instanceof OrderNotificationEvent) {
            throw new RuntimeException('Order notification event was not found.');
        }

        $payload = string_keyed_array(Arr::get($envelope, 'payload', []));
        $has_failures = false;

        foreach (OrderNotificationChannelEnum::cases() as $channel) {
            try {
                $this->deliver($event, $channel, $payload);
            } catch (Throwable $throwable) {
                $has_failures = true;
                Log::channel('stack')->critical('[OrderNotificationDeliveryService] delivery failed', [
                    'event_id' => $event->event_id,
                    'channel' => $channel->value,
                    'exception' => $throwable::class,
                    'message' => $throwable->getMessage(),
                ]);
            }
        }

        if ($has_failures) {
            throw new RuntimeException('One or more order notification deliveries failed.');
        }
    }

    /**
     * @param OrderNotificationEvent       $event
     * @param OrderNotificationChannelEnum $channel
     * @param array<string, mixed>         $payload
     *
     * @throws Throwable
     * @return void
     */
    private function deliver(OrderNotificationEvent $event, OrderNotificationChannelEnum $channel, array $payload): void
    {
        $delivery = OrderNotificationDelivery::query()->firstOrCreate(
            ['order_notification_event_id' => $event->getKey(), 'channel' => $channel],
            ['status' => OrderNotificationDeliveryStatusEnum::Pending],
        );

        if ($delivery->status === OrderNotificationDeliveryStatusEnum::Sent) {
            return;
        }

        try {
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

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @throws ConnectionException
     * @throws RequestException
     * @return string
     */
    private function sendTelegram(array $payload): string
    {
        $token = config('order-notifications.telegram.bot_token');
        $chat_id = config('order-notifications.telegram.chat_id');

        if (! is_string($token) || $token === '' || ! is_string($chat_id) || $chat_id === '') {
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
     * @throws ConnectionException
     * @throws RequestException
     * @return string
     */
    private function sendSalesDrive(array $payload): string
    {
        $endpoint = config('order-notifications.salesdrive.endpoint');
        $token = config('order-notifications.salesdrive.token');

        if (! is_string($endpoint) || $endpoint === '') {
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
