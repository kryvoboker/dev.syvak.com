<?php

declare(strict_types=1);

namespace App\Http\Controllers\Filament\Inquiries;

use App\Enums\Inquiries\InquiryTypeEnum;
use App\Models\Inquiries\InquiryAttachment;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadInquiryAttachmentController
{
    public function __invoke(InquiryAttachment $attachment): StreamedResponse
    {
        abort_unless(Filament::auth()->check(), 403);

        $attachment->loadMissing('inquiry');

        if ($attachment->inquiry?->type !== InquiryTypeEnum::Contacts) {
            abort(404);
        }

        $disk = Storage::disk($attachment->disk);

        if (! $disk->exists($attachment->path)) {
            Log::channel('stack')->error('Inquiry attachment is unavailable.', [
                'attachment_id' => $attachment->id,
                'inquiry_id' => $attachment->inquiry_id,
            ]);

            abort(404);
        }

        return $disk->download($attachment->path, $attachment->original_name);
    }
}
