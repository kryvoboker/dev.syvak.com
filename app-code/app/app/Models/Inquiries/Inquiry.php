<?php

declare(strict_types=1);

namespace App\Models\Inquiries;

use App\Enums\Inquiries\InquiryStatusEnum;
use App\Enums\Inquiries\InquiryTypeEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\Users\User;
use Database\Factories\Inquiries\InquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** @property InquiryTypeEnum $type */
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'status',
        'name',
        'email',
        'phone',
        'locale',
        'language_id',
        'user_id',
        'source_url',
        'payload',
        'inquiryable_type',
        'inquiryable_id',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => InquiryTypeEnum::class,
            'status' => InquiryStatusEnum::class,
            'language_id' => 'integer',
            'user_id' => 'integer',
            'payload' => 'array',
            'inquiryable_id' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute
     */
    /** @return Attribute<mixed, mixed> */
    public function phone(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => is_string($value) ? clear_telephone($value) : null,
            set: fn (mixed $value) => is_string($value) ? clear_telephone($value) : null,
        );
    }

    /** @return MorphTo<Model, $this> */
    public function inquiryable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Language, $this> */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<InquiryAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(InquiryAttachment::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<InquiryResponse, $this> */
    public function responses(): HasMany
    {
        return $this->hasMany(InquiryResponse::class)->latest('id');
    }

    /** @param Builder<Inquiry> $query */
    public function scopeOfType(Builder $query, InquiryTypeEnum|string $type): void
    {
        $query->where('type', $type instanceof InquiryTypeEnum ? $type->value : $type);
    }

    /** @param Builder<Inquiry> $query */
    public function scopeWithStatus(Builder $query, InquiryStatusEnum|string $status): void
    {
        $query->where('status', $status instanceof InquiryStatusEnum ? $status->value : $status);
    }

    /** @param Builder<Inquiry> $query */
    public function scopeNewestFirst(Builder $query): void
    {
        $query->orderByDesc('submitted_at')->orderByDesc('id');
    }
}
