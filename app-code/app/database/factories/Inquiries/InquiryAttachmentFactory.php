<?php

declare(strict_types=1);

namespace Database\Factories\Inquiries;

use App\Models\Inquiries\InquiryAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InquiryAttachment> */
class InquiryAttachmentFactory extends Factory
{
    protected $model = InquiryAttachment::class;

    public function definition(): array
    {
        return [
            'inquiry_id' => null,
            'disk' => 'local',
            'path' => 'images/contacts/' . $this->faker->uuid() . '.png',
            'original_name' => 'attachment.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'sort_order' => 0,
        ];
    }
}
