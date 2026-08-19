<?php

declare(strict_types=1);

namespace App\Models\ApplicationSettings;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property bool $is_active
 */
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
    #[\Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function key(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): ?string => filled($value) && is_scalar($value) ? trim((string) $value) : null,
        );
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function value(): Attribute
    {
        return Attribute::make(
            set: function (mixed $value): ?string {
                if ($value === null) {
                    return null;
                }

                if (is_bool($value)) {
                    return $value ? '1' : '0';
                }

                if (is_array($value) || is_object($value)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE) ?: null;
                }

                $string_value = is_scalar($value) ? (string) $value : '';

                return $string_value === '' ? null : $string_value;
            },
        );
    }

    /**
     * @return Collection<int, self>
     */
    public function getActiveGlobalConfigs(): Collection
    {
        /** @var Collection<int, self> $configs */
        $configs = self::query()
            ->where('is_active', true)
            ->orderBy('key')
            ->get();

        return $configs;
    }
}
