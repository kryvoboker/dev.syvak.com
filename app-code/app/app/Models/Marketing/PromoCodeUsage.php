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

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    /** @return BelongsTo<PromoCode, $this> */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /** @return BelongsTo<Orders, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<UserGroup, $this> */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }
}
