<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PageSettings\PageSetting;
use App\Services\PageSettings\ContactsFormDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverContactsFormJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @param array<string, mixed> $data */
    public function __construct(
        public readonly int $page_setting_id,
        public readonly int $inquiry_id,
        public readonly string $locale,
        public readonly int $language_id,
        public readonly array $data,
        public readonly ?string $file_path,
    ) {
    }

    public function handle(ContactsFormDeliveryService $delivery_service): void
    {
        $page_setting = PageSetting::query()->findOrFail($this->page_setting_id);

        $delivery_service->deliverStored(
            page_setting: $page_setting,
            locale: $this->locale,
            language_id: $this->language_id,
            data: $this->data,
            file_path: $this->file_path,
            inquiry_id: $this->inquiry_id,
        );
    }

    public function failed(Throwable $throwable): void
    {
        Log::channel('stack')->error('Queued Contacts form delivery failed.', [
            'page_setting_id' => $this->page_setting_id,
            'inquiry_id' => $this->inquiry_id,
            'locale' => $this->locale,
            'exception' => $throwable,
        ]);
    }
}
