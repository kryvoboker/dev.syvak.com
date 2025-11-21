<?php

declare(strict_types=1);

namespace App\Models\Settings;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol_left',
        'symbol_right',
        'decimal_places',
        'exchange_rate',
        'is_active',
        'is_default',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'exchange_rate'  => 'decimal:6',
            'is_active'      => 'boolean',
            'is_default'     => 'boolean',
        ];
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function booted(): void
    {
        // Ensure only one default currency
        static::saving(function (Currency $currency) {
            if ($currency->is_default) {
                // Set all other currencies as non-default
                static::where('id', '!=', $currency->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                // Default currency must be active
                $currency->is_active = true;
            } else {
                // Ensure there is always one default currency
                $default_exists = static::where('is_default', true)
                    ->where('id', '!=', $currency->id)
                    ->exists();

                if (!$default_exists) {
                    $currency->is_default = true;
                }
            }
        });

        // Prevent deletion of default language
        static::deleting(function (Language $language) {
            if ($language->is_default) {
                throw new Exception(__('admin/settings/currencies.error_cant_delete_default_currency'));
            }

            $active_currencies = static::where('is_active', true)->count();

            if ($active_currencies == 1) {
                throw new Exception(__('admin/settings/currencies.error_cant_delete_last_active_currency'));
            }
        });
    }

    /**
     * @return Collection
     */
    public function getAllActiveCurrencies(): Collection
    {
        return self::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('name')
            ->get();
    }

    /**
     * @return Currency|null
     */
    public function getDefaultActiveCurrency(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    /**
     * @return Collection
     */
    public function getAllCurrencies(): Collection
    {
        return self::query()
            ->orderByDesc('is_default')
            ->orderByDesc('name')
            ->get();
    }
}
