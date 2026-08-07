<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Models\PageSettings\PageSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Message;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final readonly class ContactsFormDeliveryService
{
    public function __construct(
        private ContactsPageService $contacts_page_service,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function deliver(PageSetting $page_setting, string $locale, int $language_id, array $data, ?UploadedFile $file = null): void
    {
        $settings = $this->contacts_page_service->getSettings($page_setting);
        $file_path = $this->storeFile($settings, $file);
        $placeholders = [
            '{name}' => (string) Arr::get($data, 'name', ''),
            '{email}' => (string) Arr::get($data, 'email', ''),
            '{phone}' => (string) Arr::get($data, 'phone', ''),
            '{text}' => (string) Arr::get($data, 'text', ''),
            '{file}' => $file_path ?? '',
        ];
        $destinations = Arr::get($settings, 'contact_form.destinations', []);
        $sent = false;

        if ((bool) Arr::get($destinations, 'email.enabled', false)) {
            try {
                $this->sendEmail($settings, $language_id, $placeholders);
                $sent = true;
            } catch (Throwable $throwable) {
                Log::channel('stack')->error('Contacts form email delivery failed.', [
                    'page_setting_id' => $page_setting->id,
                    'locale' => $locale,
                    'exception' => $throwable,
                ]);
            }
        }

        if ((bool) Arr::get($destinations, 'telegram.enabled', false)) {
            try {
                $this->sendTelegram($settings, $language_id, $placeholders);
                $sent = true;
            } catch (Throwable $throwable) {
                Log::channel('stack')->error('Contacts form Telegram delivery failed.', [
                    'page_setting_id' => $page_setting->id,
                    'locale' => $locale,
                    'exception' => $throwable,
                ]);
            }
        }

        if (! $sent) {
            throw new RuntimeException('No Contacts form destination accepted the request.');
        }
    }

    /** @param array<string, mixed> $settings @param array<string, string> $placeholders */
    private function sendEmail(array $settings, int $language_id, array $placeholders): void
    {
        $recipient = Str::trim((string) Arr::get($settings, 'contact_form.destinations.email.address', ''));

        if ($recipient === '') {
            throw new RuntimeException('Contacts email recipient is not configured.');
        }

        $template = $this->resolveLocalizedTemplate(Arr::get($settings, 'email.templates', []), $language_id);
        $subject = $this->replacePlaceholders((string) Arr::get($template, 'subject', ''), $placeholders);
        $body = $this->replacePlaceholders((string) Arr::get($template, 'body', ''), $placeholders);

        Mail::raw($body, function (Message $message) use ($recipient, $subject): void {
            $message->to($recipient)->subject($subject);
        });
    }

    /** @param array<string, mixed> $settings @param array<string, string> $placeholders */
    private function sendTelegram(array $settings, int $language_id, array $placeholders): void
    {
        $token = Str::trim((string) config('monolog.telegram_token', ''));
        $chat_id = Str::trim((string) Arr::get($settings, 'contact_form.destinations.telegram.chat_id', ''));

        if ($token === '' || $chat_id === '') {
            throw new RuntimeException('Contacts Telegram credentials are not configured.');
        }

        $template = $this->resolveLocalizedTemplate(Arr::get($settings, 'telegram.templates', []), $language_id);
        $body = $this->replacePlaceholders((string) Arr::get($template, 'body', ''), $placeholders);
        $response = Http::asForm()
            ->timeout(10)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chat_id,
                'text' => $body,
            ]);

        if ($response->successful() === false || $response->json('ok') !== true) {
            throw new RuntimeException('Contacts Telegram API rejected the request.');
        }
    }

    /** @param array<string, mixed> $settings */
    private function storeFile(array $settings, ?UploadedFile $file): ?string
    {
        if (! $file instanceof UploadedFile) {
            return null;
        }

        $upload_path = resolve_upload_path_placeholders((string) Arr::get(
            $settings,
            'contact_form.fields.file.upload_path',
            'images/contacts/{year}/{month}',
        ));

        return $file->store($upload_path, 'public');
    }

    /** @param mixed $templates @return array<string, string> */
    private function resolveLocalizedTemplate(mixed $templates, int $language_id): array
    {
        $templates = is_array($templates) ? $templates : [];
        $template = Arr::get($templates, (string) $language_id);

        if (is_array($template)) {
            return $template;
        }

        $first_template = Arr::first($templates);

        return is_array($first_template) ? $first_template : [];
    }

    /** @param array<string, string> $placeholders */
    private function replacePlaceholders(string $template, array $placeholders): string
    {
        return Str::replace(array_keys($placeholders), array_values($placeholders), $template);
    }
}
