<?php

declare(strict_types=1);

namespace Database\Factories\Inquiries;

use App\Enums\Inquiries\InquiryResponseDeliveryStatusEnum;
use App\Models\Inquiries\InquiryResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InquiryResponse> */
class InquiryResponseFactory extends Factory
{
    protected $model = InquiryResponse::class;

    public function definition(): array
    {
        return [
            'inquiry_id' => null,
            'subject' => $this->faker->sentence(4),
            'admin_user_id' => null,
            'admin_name' => 'Адмін',
            'body_html' => '<p>' . $this->faker->sentence() . '</p>',
            'recipient_email' => $this->faker->safeEmail(),
            'delivery_status' => InquiryResponseDeliveryStatusEnum::NotSent,
            'delivery_error' => null,
            'response_at' => now(),
            'sent_at' => null,
        ];
    }
}
