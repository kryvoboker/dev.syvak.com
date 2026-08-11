<?php

declare(strict_types=1);

namespace App\Models\Inquiries;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ContactInquiry extends Model
{
    /** @use HasFactory<\Database\Factories\Inquiries\ContactInquiryFactory> */
    use HasFactory;

    protected $fillable = [
        'message',
        'submitted_fields',
    ];

    protected function casts(): array
    {
        return [
            'submitted_fields' => 'array',
        ];
    }

    /** @return MorphOne<Inquiry, $this> */
    public function inquiry(): MorphOne
    {
        return $this->morphOne(Inquiry::class, 'inquiryable');
    }
}
