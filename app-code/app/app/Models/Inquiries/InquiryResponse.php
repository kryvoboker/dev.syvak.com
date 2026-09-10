<?php

declare(strict_types=1);

namespace App\Models\Inquiries;

use App\Enums\Inquiries\InquiryResponseDeliveryStatusEnum;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inquiry_id
 * @property string $subject
 * @property int|null $admin_user_id
 * @property string|null $admin_name
 * @property string $body_html
 * @property string $recipient_email
 * @property InquiryResponseDeliveryStatusEnum $delivery_status
 * @property string|null $delivery_error
 * @property-read Inquiry|null $inquiry
 */
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
