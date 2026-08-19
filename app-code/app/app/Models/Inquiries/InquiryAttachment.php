<?php

declare(strict_types=1);

namespace App\Models\Inquiries;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inquiry_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int $sort_order
 * @property-read Inquiry|null $inquiry
 */
class InquiryAttachment extends Model
{
    /** @use HasFactory<\Database\Factories\Inquiries\InquiryAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'sort_order',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'inquiry_id' => 'integer',
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @phpstan-return BelongsTo<Inquiry, $this>
     * @psalm-return BelongsTo<Inquiry, self>
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }
}
