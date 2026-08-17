<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Inquiries\InquiryResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryResponseMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly InquiryResponse $response)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: (string) $this->response->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'storefront.layouts.emails.inquiries.response',
            with: [
                'body_html' => $this->response->body_html,
                'admin_name' => $this->response->admin_name,
            ],
        );
    }

    /** @return array<int, mixed> */
    public function attachments(): array
    {
        return [];
    }
}
