<?php

declare(strict_types=1);

namespace App\Services\Inquiries;

use App\Enums\Inquiries\InquiryResponseDeliveryStatusEnum;
use App\Mail\InquiryResponseMail;
use App\Models\Inquiries\Inquiry;
use App\Models\Inquiries\InquiryResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Throwable;

final readonly class InquiryResponseService
{
    /** @param array<string, mixed> $data */
    public function createAndDeliver(Inquiry $inquiry, array $data): InquiryResponse
    {
        $body_html = $this->sanitizeHtml((string) ($data['body_html'] ?? ''));
        $recipient_email = $this->resolveNullableString($inquiry->email);
        $subject = $this->resolveNullableString($data['subject'] ?? null);

        if ($subject === null) {
            throw new InvalidArgumentException('Inquiry response subject is required.');
        }

        $response = $inquiry->responses()->create([
            'subject' => $subject,
            'admin_user_id' => auth()->id(),
            'admin_name' => $this->resolveNullableString($data['admin_name'] ?? null) ?? 'Адмін',
            'body_html' => $body_html,
            'recipient_email' => $recipient_email,
            'delivery_status' => InquiryResponseDeliveryStatusEnum::NotSent,
            'response_at' => $this->resolveResponseDate($data['response_at'] ?? null),
        ]);

        if ($recipient_email === null) {
            Log::channel('daily')->warning('Inquiry response was saved without delivery recipient.', [
                'inquiry_id' => $inquiry->id,
                'response_id' => $response->id,
            ]);

            return $response;
        }

        try {
            $inquiry->loadMissing('inquiryable');
            Mail::to($recipient_email)->send(new InquiryResponseMail($response));
            $response->forceFill([
                'delivery_status' => InquiryResponseDeliveryStatusEnum::Sent,
                'sent_at' => now(),
                'delivery_error' => null,
            ])->save();

            Log::channel('daily')->info('Inquiry response delivered.', [
                'inquiry_id' => $inquiry->id,
                'response_id' => $response->id,
                'recipient_domain' => Str::after($recipient_email, '@'),
            ]);
        } catch (Throwable $throwable) {
            $response->forceFill([
                'delivery_status' => InquiryResponseDeliveryStatusEnum::Failed,
                'delivery_error' => Str::limit($throwable->getMessage(), 1000),
            ])->save();

            Log::channel('stack')->error('Inquiry response delivery failed.', [
                'inquiry_id' => $inquiry->id,
                'response_id' => $response->id,
                'recipient_domain' => Str::after($recipient_email, '@'),
                'exception' => $throwable,
            ]);
        }

        return $response->fresh() ?? $response;
    }

    private function sanitizeHtml(string $body_html): string
    {
        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowElement('table', '*')
            ->allowElement('thead', '*')
            ->allowElement('tbody', '*')
            ->allowElement('tfoot', '*')
            ->allowElement('tr', '*')
            ->allowElement('th', '*')
            ->allowElement('td', '*')
            ->allowElement('br')
            ->allowElement('p')
            ->allowElement('div')
            ->allowElement('span')
            ->allowElement('a', ['href', 'title'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->forceHttpsUrls();

        return (new HtmlSanitizer($config))->sanitize($body_html);
    }

    private function resolveNullableString(mixed $value): ?string
    {
        $value = Str::trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveResponseDate(mixed $value): Carbon
    {
        return filled($value) ? Carbon::parse((string) $value) : now();
    }
}
