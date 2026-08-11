<?php

declare(strict_types=1);

namespace Database\Factories\Inquiries;

use App\Models\Inquiries\ContactInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactInquiry> */
class ContactInquiryFactory extends Factory
{
    protected $model = ContactInquiry::class;

    public function definition(): array
    {
        return [
            'message' => $this->faker->paragraph(),
            'submitted_fields' => [],
        ];
    }
}
