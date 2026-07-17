<?php

declare(strict_types=1);

namespace App\Models\Carts;

use App\Enums\Cart\CartModeEnum;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'cart_mode',
        'product_variant_id',
        'quantity',
        'chosen_attributes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'cart_mode' => CartModeEnum::class,
            'product_variant_id' => 'integer',
            'quantity' => 'integer',
            'chosen_attributes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
