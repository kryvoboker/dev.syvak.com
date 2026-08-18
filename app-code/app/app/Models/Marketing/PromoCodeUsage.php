<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use App\Models\Orders\Orders;
use App\Models\Users\User;
use App\Models\Users\UserGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCodeUsage extends Model
{
    protected $fillable = [
        'promo_code_id',
        'order_id',
        'user_id',
        'user_group_id',
        'consumer_key',
        'used_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    /** @phpstan-return BelongsTo<PromoCode, $this>
     * @psalm-return BelongsTo<PromoCode, self>
     */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /** @phpstan-return BelongsTo<Orders, $this>
     * @psalm-return BelongsTo<Orders, self>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }

    /** @phpstan-return BelongsTo<User, $this>
     * @psalm-return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @phpstan-return BelongsTo<UserGroup, $this>
     * @psalm-return BelongsTo<UserGroup, self>
     */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }
}
