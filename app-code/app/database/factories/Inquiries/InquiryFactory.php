<?php

declare(strict_types=1);

namespace Database\Factories\Inquiries;

use App\Enums\Inquiries\InquiryStatusEnum;
use App\Enums\Inquiries\InquiryTypeEnum;
use App\Models\Inquiries\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Inquiry> */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    public function definition(): array
    {
        return [
            'type' => InquiryTypeEnum::Contacts,
            'status' => InquiryStatusEnum::New,
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => null,
            'locale' => 'en',
            'language_id' => null,
            'user_id' => null,
            'source_url' => 'https://example.com/en/contacts',
            'payload' => [],
            'submitted_at' => now(),
        ];
    }
}
