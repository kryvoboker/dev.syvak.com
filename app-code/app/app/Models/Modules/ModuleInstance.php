<?php

declare(strict_types=1);

namespace App\Models\Modules;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $settings
 * @property array<string, mixed>|null $meta
 */
class ModuleInstance extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'module_definition_id',
        'name',
        'placement',
        'context_key',
        'is_enabled',
        'sort_order',
        'settings',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'module_definition_id' => 'integer',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * @phpstan-return BelongsTo<ModuleDefinition, $this>
     * @psalm-return BelongsTo<ModuleDefinition, self>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ModuleDefinition::class, 'module_definition_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForContext(Builder $query, ?string $placement, ?string $context_key): Builder
    {
        return $query
            ->when(filled($placement), fn (Builder $builder): Builder => $builder->where('placement', $placement))
            ->when(filled($context_key), fn (Builder $builder): Builder => $builder->where('context_key', $context_key));
    }
}
