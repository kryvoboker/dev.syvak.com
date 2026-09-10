<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class OrderNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @param array<string, mixed> $payload */
    public function __construct(public readonly array $payload)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Order ' . string_value(data_get($this->payload, 'order.number', '')));
    }

    public function content(): Content
    {
        return new Content(
            view: 'storefront.layouts.emails.orders.notification',
            with: ['payload' => $this->payload],
        );
    }

    /** @return array<int, mixed> */
    public function attachments(): array
    {
        return [];
    }
}
