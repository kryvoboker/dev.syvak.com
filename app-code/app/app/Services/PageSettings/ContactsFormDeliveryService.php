<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Jobs\DeliverContactsFormJob;
use App\Models\PageSettings\PageSetting;
use App\Services\Inquiries\InquiryPersistenceService;
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
        private InquiryPersistenceService $inquiry_persistence_service,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function deliver(PageSetting $page_setting, string $locale, int $language_id, array $data, ?UploadedFile $file = null): void
    {
        $settings = $this->contacts_page_service->getSettings($page_setting);
        $this->ensureDestinationIsConfigured($settings);
        $file_path = $this->storeFile($settings, $file);
        $queue_data = Arr::except($data, ['file']);
        /** @var array<string, mixed> $queue_data */
        $inquiry = $this->inquiry_persistence_service->persistContact(
            locale      : $locale,
            language_id : $language_id,
            data        : $queue_data,
            file_path   : $file_path,
            file        : $file,
        );

        DeliverContactsFormJob::dispatch(
            page_setting_id: (int) $page_setting->id,
            inquiry_id     : (int) $inquiry->id,
            locale         : $locale,
            language_id    : $language_id,
            data           : $queue_data,
            file_path      : $file_path,
        );

        Log::channel('daily')->info('Contact inquiry delivery dispatched.', [
            'inquiry_id' => $inquiry->id,
            'page_setting_id' => $page_setting->id,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function deliverStored(PageSetting $page_setting, string $locale, int $language_id, array $data, ?string $file_path = null, ?int $inquiry_id = null): void
    {
        $settings = $this->contacts_page_service->getSettings($page_setting);
        $placeholders = [
            '{name}' => $this->stringValue(Arr::get($data, 'name', '')),
            '{email}' => $this->stringValue(Arr::get($data, 'email', '')),
            '{phone}' => $this->stringValue(Arr::get($data, 'phone', '')),
            '{text}' => $this->stringValue(Arr::get($data, 'text', '')),
        ];
        $destinations = $this->arrayValue(Arr::get($settings, 'contact_form.destinations', []));
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
                    'inquiry_id' => $inquiry_id,
                    'locale' => $locale,
                    'exception' => $throwable,
                ]);
            }
        }

        $telegram_enabled = (bool) Arr::get($destinations, 'telegram.enabled', false);
        $telegram_token = Str::trim($this->stringValue(Arr::get($destinations, 'telegram.bot_token', '')));

        if ($telegram_enabled && $telegram_token === '') {
            Log::channel('stack')->critical('Contacts Telegram delivery skipped because the bot token is not configured.', [
                'page_setting_id' => $page_setting->id,
                'inquiry_id' => $inquiry_id,
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
                    'inquiry_id' => $inquiry_id,
                    'locale' => $locale,
                    'exception' => $throwable,
                ]);
            }
        }

        if (!$delivery_attempted) {
            Log::channel('stack')->critical('Contacts form request was not delivered to any configured destination.', [
                'page_setting_id' => $page_setting->id,
                'inquiry_id' => $inquiry_id,
                'locale' => $locale,
            ]);
        }
    }

    /** @param array<string, mixed> $settings */
    private function ensureDestinationIsConfigured(array $settings): void
    {
        $destinations = $this->arrayValue(Arr::get($settings, 'contact_form.destinations', []));
        $email_enabled = (bool)Arr::get($destinations, 'email.enabled', false);
        $telegram_enabled = (bool)Arr::get($destinations, 'telegram.enabled', false);

        if (!$email_enabled && !$telegram_enabled) {
            throw new RuntimeException('No Contacts form destination is enabled.');
        }
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, string> $placeholders
     */
    private function sendEmail(array $settings, int $language_id, array $placeholders, ?string $file_path, bool $send_file): void
    {
        $recipient = Str::trim($this->stringValue(Arr::get($settings, 'contact_form.destinations.email.address', '')));

        if ($recipient === '') {
            throw new RuntimeException('Contacts email recipient is not configured.');
        }

        $file_url = $send_file ? $this->resolveFileUrl($file_path) : '';
        $placeholders['{file}'] = $file_url;
        $template = $this->resolveLocalizedTemplate(Arr::get($settings, 'email.templates', []), $language_id);
        $subject = $this->replacePlaceholders($this->stringValue(Arr::get($template, 'subject', '')), $placeholders);
        $body = $this->replacePlaceholders($this->stringValue(Arr::get($template, 'body', '')), $placeholders);
        $body = $this->appendFileUrlIfMissing($body, $file_url, $send_file, $this->stringValue(Arr::get($template, 'body', '')));

        Mail::raw($body, function (Message $message) use ($recipient, $subject, $file_path, $send_file): void {
            $message->to($recipient)->subject($subject);

            if ($send_file && $this->publicFileExists($file_path)) {
                $file_contents = Storage::disk('public')->get($file_path ?? '');

                if (! is_string($file_contents)) {
                    return;
                }

                $message->attachData(
                    $file_contents,
                    basename($file_path ?? ''),
                    ['mime' => Storage::disk('public')->mimeType($file_path ?? '')],
                );
            }
        });
    }

    /**
     * @param array<string, mixed>  $settings
     * @param array<string, string> $placeholders
     * @param int                  $language_id
     * @param string|null          $file_path
     * @param bool                 $send_file
     * @param string               $bot_token
     *
     * @throws ConnectionException
     * @return void
     */
    private function sendTelegram(array $settings, int $language_id, array $placeholders, ?string $file_path, bool $send_file, string $bot_token): void
    {
        $chat_id = Str::trim($this->stringValue(Arr::get($settings, 'contact_form.destinations.telegram.chat_id', '')));

        if ($chat_id === '') {
            throw new RuntimeException('Contacts Telegram chat ID is not configured.');
        }

        $file_url = $send_file ? $this->resolveFileUrl($file_path) : '';
        $placeholders['{file}'] = $file_url;
        $template = $this->resolveLocalizedTemplate(Arr::get($settings, 'telegram.templates', []), $language_id);
        $body = $this->replacePlaceholders($this->stringValue(Arr::get($template, 'body', '')), $placeholders);
        $body = $this->appendFileUrlIfMissing($body, $file_url, $send_file, $this->stringValue(Arr::get($template, 'body', '')));
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
            $file_contents = Storage::disk('public')->get($file_path ?? '');

            if (! is_string($file_contents)) {
                throw new RuntimeException('Contacts Telegram file could not be read.');
            }

            $file_response = Http::attach(
                'document',
                $file_contents,
                basename($file_path ?? ''),
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

        $upload_path = resolve_upload_path_placeholders($this->stringValue(Arr::get(
            $settings,
            'contact_form.fields.file.upload_path',
            'images/contacts/{year}/{month}',
        )));

        return $file->store($upload_path, 'public') ?: null;
    }

    private function publicFileExists(?string $file_path): bool
    {
        return filled($file_path) && Storage::disk('public')->exists($file_path);
    }

    private function resolveFileUrl(?string $file_path): string
    {
        return $this->publicFileExists($file_path)
            ? Storage::disk('public')->url($file_path ?? '')
            : '';
    }

    /** @param mixed $templates @return array<string, string> */
    /** @return array<string, string> */
    private function resolveLocalizedTemplate(mixed $templates, int $language_id): array
    {
        $templates = is_array($templates) ? $templates : [];
        $template = Arr::get($templates, (string)$language_id);

        if (is_array($template)) {
            return $this->stringTemplate($template);
        }

        $first_template = Arr::first($templates);

        return is_array($first_template) ? $this->stringTemplate($first_template) : [];
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

    /**
     * @return array<string|int, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @param array<mixed, mixed> $value
     * @return array<string, string>
     */
    private function stringTemplate(array $value): array
    {
        $result = [];

        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $this->stringValue($item);
            }
        }

        return $result;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
