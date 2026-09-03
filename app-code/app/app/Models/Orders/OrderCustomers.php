<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Models\Users\User;
use App\Models\Users\UserGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $user_id
 * @property int|null $user_group_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $telephone
 * @property bool $no_call
 */
class OrderCustomers extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'user_group_id',
        'first_name',
        'last_name',
        'email',
        'telephone',
        'no_call',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'user_id' => 'integer',
            'user_group_id' => 'integer',
            'no_call' => 'boolean',
        ];
    }

    /**
     * @phpstan-return BelongsTo<Orders, $this>
     * @psalm-return BelongsTo<Orders, self>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }

    /**
     * @phpstan-return BelongsTo<User, $this>
     * @psalm-return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @phpstan-return BelongsTo<UserGroup, $this>
     * @psalm-return BelongsTo<UserGroup, self>
     */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }
}
