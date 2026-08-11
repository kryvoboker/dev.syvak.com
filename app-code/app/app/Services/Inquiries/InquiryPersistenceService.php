<?php

declare(strict_types=1);

namespace App\Services\Inquiries;

use App\Enums\Inquiries\InquiryStatusEnum;
use App\Enums\Inquiries\InquiryTypeEnum;
use App\Models\Inquiries\ContactInquiry;
use App\Models\Inquiries\Inquiry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final readonly class InquiryPersistenceService
{
    /**
     * @param array<string, mixed> $data
     */
    public function persistContact(
        string $locale,
        int $language_id,
        array $data,
        ?string $file_path = null,
        ?UploadedFile $file = null,
    ): Inquiry {
        try {
            $inquiry = DB::transaction(function () use ($locale, $language_id, $data, $file_path, $file): Inquiry {
                $contact_inquiry = ContactInquiry::query()->create([
                    'message' => $this->resolveNullableString(Arr::get($data, 'text')),
                    'submitted_fields' => Arr::except($data, ['file']),
                ]);

                $inquiry = $contact_inquiry->inquiry()->create([
                    'type' => InquiryTypeEnum::Contacts,
                    'status' => InquiryStatusEnum::New,
                    'name' => $this->resolveNullableString(Arr::get($data, 'name')),
                    'email' => $this->resolveNullableString(Arr::get($data, 'email')),
                    'phone' => $this->resolveNullableString(Arr::get($data, 'phone')),
                    'locale' => Str::trim($locale),
                    'language_id' => $language_id,
                    'user_id' => auth()->id(),
                    'source_url' => request()->fullUrl(),
                    'payload' => Arr::except($data, ['file']),
                    'submitted_at' => now(),
                ]);

                if ($file_path !== null && $file instanceof UploadedFile) {
                    $inquiry->attachments()->create([
                        'disk' => 'public',
                        'path' => $file_path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'sort_order' => 0,
                    ]);
                }

                return $inquiry;
            });

            Log::channel('daily')->info('Contact inquiry persisted.', [
                'inquiry_id' => $inquiry->id,
                'inquiry_type' => InquiryTypeEnum::Contacts->value,
                'has_attachment' => $file_path !== null,
            ]);

            return $inquiry;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Failed to persist contact inquiry.', [
                'inquiry_type' => InquiryTypeEnum::Contacts->value,
                'language_id' => $language_id,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    private function resolveNullableString(mixed $value): ?string
    {
        $value = Str::trim((string) $value);

        return $value === '' ? null : $value;
    }
}
