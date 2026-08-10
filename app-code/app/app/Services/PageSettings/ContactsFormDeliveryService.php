<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Jobs\DeliverContactsFormJob;
use App\Models\PageSettings\PageSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Message;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
        $this->ensureDestinationIsConfigured($settings);
        $file_path = $this->storeFile($settings, $file);
        $queue_data = Arr::except($data, ['file']);

        DeliverContactsFormJob::dispatch(
            page_setting_id: (int)$page_setting->id,
            locale         : $locale,
            language_id    : $language_id,
            data           : $queue_data,
            file_path      : $file_path,
        );
    }

    /** @param array<string, mixed> $data */
    public function deliverStored(PageSetting $page_setting, string $locale, int $language_id, array $data, ?string $file_path = null): void
    {
        $settings = $this->contacts_page_service->getSettings($page_setting);
        $placeholders = [
            '{name}' => (string)Arr::get($data, 'name', ''),
            '{email}' => (string)Arr::get($data, 'email', ''),
            '{phone}' => (string)Arr::get($data, 'phone', ''),
            '{text}' => (string)Arr::get($data, 'text', ''),
        ];
        $destinations = Arr::get($settings, 'contact_form.destinations', []);
        $delivery_attempted = false;

        if (Arr::get($destinations, 'email.enabled', false)) {
            $delivery_attempted = true;

            try {
                $this->sendEmail(
                    settings    : $settings,
                    language_id : $language_id,
                    placeholders: $placeholders,
                    file_path   : $file_path,
                    send_file   : (bool)Arr::get($destinations, 'email.send_file', false),
                );
            } catch (Throwable $throwable) {
                Log::channel('stack')->error('Contacts form email delivery failed.', [
                    'page_setting_id' => $page_setting->id,
                    'locale' => $locale,
                    'exception' => $throwable,
                ]);
            }
        }

        $telegram_enabled = (bool) Arr::get($destinations, 'telegram.enabled', false);
        $telegram_token = Str::trim((string) Arr::get($destinations, 'telegram.bot_token', ''));

        if ($telegram_enabled && $telegram_token === '') {
            Log::channel('stack')->critical('Contacts Telegram delivery skipped because the bot token is not configured.', [
                'page_setting_id' => $page_setting->id,
                'locale' => $locale,
            ]);
        } elseif ($telegram_enabled) {
            $delivery_attempted = true;

            try {
                $this->sendTelegram(
                    settings    : $settings,
                    language_id : $language_id,
                    placeholders: $placeholders,
                    file_path   : $file_path,
                    send_file   : (bool)Arr::get($destinations, 'telegram.send_file', false),
                    bot_token   : $telegram_token,
                );
            } catch (Throwable $throwable) {
                Log::channel('stack')->error('Contacts form Telegram delivery failed.', [
                    'page_setting_id' => $page_setting->id,
                    'locale' => $locale,
                    'exception' => $throwable,
                ]);
            }
        }

        if (!$delivery_attempted) {
            Log::channel('stack')->critical('Contacts form request was not delivered to any configured destination.', [
                'page_setting_id' => $page_setting->id,
                'locale' => $locale,
            ]);
        }
    }

    /** @param array<string, mixed> $settings */
    private function ensureDestinationIsConfigured(array $settings): void
    {
        $destinations = Arr::get($settings, 'contact_form.destinations', []);
        $email_enabled = (bool)Arr::get($destinations, 'email.enabled', false);
        $telegram_enabled = (bool)Arr::get($destinations, 'telegram.enabled', false);

        if (!$email_enabled && !$telegram_enabled) {
            throw new RuntimeException('No Contacts form destination is enabled.');
        }
    }

    /** @param array<string, mixed> $settings @param array<string, string> $placeholders */
    private function sendEmail(array $settings, int $language_id, array $placeholders, ?string $file_path, bool $send_file): void
    {
        $recipient = Str::trim((string)Arr::get($settings, 'contact_form.destinations.email.address', ''));

        if ($recipient === '') {
            throw new RuntimeException('Contacts email recipient is not configured.');
        }

        $file_url = $send_file ? $this->resolveFileUrl($file_path) : '';
        $placeholders['{file}'] = $file_url;
        $template = $this->resolveLocalizedTemplate(Arr::get($settings, 'email.templates', []), $language_id);
        $subject = $this->replacePlaceholders((string)Arr::get($template, 'subject', ''), $placeholders);
        $body = $this->replacePlaceholders((string)Arr::get($template, 'body', ''), $placeholders);
        $body = $this->appendFileUrlIfMissing($body, $file_url, $send_file, (string)Arr::get($template, 'body', ''));

        Mail::raw($body, function (Message $message) use ($recipient, $subject, $file_path, $send_file): void {
            $message->to($recipient)->subject($subject);

            if ($send_file && $this->publicFileExists($file_path)) {
                $message->attachData(
                    Storage::disk('public')->get((string)$file_path),
                    basename((string)$file_path),
                    ['mime' => Storage::disk('public')->mimeType((string)$file_path)],
                );
            }
        });
    }

    /**
     * @param array<string, mixed> $settings @param array<string, string> $placeholders
     * @param int                  $language_id
     * @param array                $placeholders
     * @param string|null          $file_path
     * @param bool                 $send_file
     * @param string               $bot_token
     *
     * @throws ConnectionException
     * @return void
     */
    private function sendTelegram(array $settings, int $language_id, array $placeholders, ?string $file_path, bool $send_file, string $bot_token): void
    {
        $chat_id = Str::trim((string)Arr::get($settings, 'contact_form.destinations.telegram.chat_id', ''));

        if ($chat_id === '') {
            throw new RuntimeException('Contacts Telegram chat ID is not configured.');
        }

        $file_url = $send_file ? $this->resolveFileUrl($file_path) : '';
        $placeholders['{file}'] = $file_url;
        $template = $this->resolveLocalizedTemplate(Arr::get($settings, 'telegram.templates', []), $language_id);
        $body = $this->replacePlaceholders((string)Arr::get($template, 'body', ''), $placeholders);
        $body = $this->appendFileUrlIfMissing($body, $file_url, $send_file, (string)Arr::get($template, 'body', ''));
        $response = Http::asForm()
            ->timeout(10)
            ->post("https://api.telegram.org/bot$bot_token/sendMessage", [
                'chat_id' => $chat_id,
                'text' => $body,
            ]);

        if ($response->successful() === false || $response->json('ok') !== true) {
            throw new RuntimeException('Contacts Telegram API rejected the request.');
        }

        if ($send_file && $this->publicFileExists($file_path)) {
            $file_response = Http::attach(
                'document',
                Storage::disk('public')->get((string)$file_path),
                basename((string)$file_path),
            )
                ->timeout(30)
                ->post("https://api.telegram.org/bot$bot_token/sendDocument", [
                    'chat_id' => $chat_id,
                ]);

            if ($file_response->successful() === false || $file_response->json('ok') !== true) {
                throw new RuntimeException('Contacts Telegram file delivery failed.');
            }
        }
    }

    /** @param array<string, mixed> $settings */
    private function storeFile(array $settings, ?UploadedFile $file): ?string
    {
        if (!$file instanceof UploadedFile) {
            return null;
        }

        $upload_path = resolve_upload_path_placeholders((string)Arr::get(
            $settings,
            'contact_form.fields.file.upload_path',
            'images/contacts/{year}/{month}',
        ));

        return $file->store($upload_path, 'public');
    }

    private function publicFileExists(?string $file_path): bool
    {
        return filled($file_path) && Storage::disk('public')->exists((string)$file_path);
    }

    private function resolveFileUrl(?string $file_path): string
    {
        return $this->publicFileExists($file_path)
            ? Storage::disk('public')->url((string)$file_path)
            : '';
    }

    /** @param mixed $templates @return array<string, string> */
    private function resolveLocalizedTemplate(mixed $templates, int $language_id): array
    {
        $templates = is_array($templates) ? $templates : [];
        $template = Arr::get($templates, (string)$language_id);

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

    private function appendFileUrlIfMissing(string $body, string $file_url, bool $send_file, string $template): string
    {
        if (!$send_file || $file_url === '' || Str::contains($template, '{file}')) {
            return $body;
        }

        return Str::finish($body, PHP_EOL) . $file_url;
    }
}
