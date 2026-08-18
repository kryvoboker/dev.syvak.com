<?php

declare(strict_types=1);

namespace App\Models\Inquiries;

use App\Enums\Inquiries\InquiryResponseDeliveryStatusEnum;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property InquiryResponseDeliveryStatusEnum $delivery_status */
class InquiryResponse extends Model
{
    /** @use HasFactory<\Database\Factories\Inquiries\InquiryResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'subject',
        'admin_user_id',
        'admin_name',
        'body_html',
        'recipient_email',
        'delivery_status',
        'delivery_error',
        'response_at',
        'sent_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'inquiry_id' => 'integer',
            'admin_user_id' => 'integer',
            'delivery_status' => InquiryResponseDeliveryStatusEnum::class,
            'response_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /** @phpstan-return BelongsTo<Inquiry, $this>
     * @psalm-return BelongsTo<Inquiry, self>
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    /** @phpstan-return BelongsTo<User, $this>
     * @psalm-return BelongsTo<User, self>
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
