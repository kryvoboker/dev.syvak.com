<?php

declare(strict_types=1);

namespace App\Models\ApplicationSettings;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GlobalConfig extends Model
{
    protected $fillable = [
        'key',
        'value',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function key(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): ?string => filled($value) ? trim((string) $value) : null,
        );
    }

    public function value(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): ?string => blank($value) ? null : (string) $value,
        );
    }

    /**
     * @return Collection<int, self>
     */
    public function getActiveGlobalConfigs(): Collection
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('key')
            ->get();
    }
}
