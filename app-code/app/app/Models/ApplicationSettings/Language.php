<?php

declare(strict_types=1);

namespace App\Models\ApplicationSettings;

use App\Models\Slug;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class Language extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'is_default',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Slug, $this>
     */
    public function slug(): HasMany
    {
        return $this->hasMany(Slug::class);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Ensure only one default language
        static::saving(function (Language $language) {
            if ($language->is_default) {
                // Set all other languages as non-default
                static::where('id', '!=', $language->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                // Default language must be active
                $language->is_active = true;
            } else {
                // Ensure there is always one default language
                $default_exists = static::where('is_default', true)
                    ->where('id', '!=', $language->id)
                    ->exists();

                if (! $default_exists) {
                    $language->is_default = true;
                }
            }
        });

        // Prevent deletion of default language
        static::deleting(function (Language $language) {
            if ($language->is_default) {
                throw new Exception(__('admin/settings/languages.errors.cant_delete_default_language'));
            }

            $active_langs = static::where('is_active', true)->count();

            if ($active_langs == 1) {
                throw new Exception(__('admin/settings/languages.errors.cant_delete_last_active_language'));
            }
        });
    }

    /**
     * Get validation rules for the model.
     */
    public static function validationRules(?int $id = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:10',
                'alpha_dash',
                'lowercase',
                Rule::unique('languages', 'code')->ignore($id),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'is_active' => [
                'boolean',
            ],
            'is_default' => [
                'boolean',
            ],
        ];
    }

    /**
     * @return Collection<Language>
     */
    public function getActiveLanguages(): Collection
    {
        return self::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function getLanguageByCode(string $code): ?self
    {
        return self::query()
            ->where('code', $code)
            ->first();
    }

    public function getDefaultLanguage(): ?self
    {
        return self::query()
            ->where('is_default', true)
            ->first();
    }

    public function getActiveLanguagesWithoutExceptCode(string $code): Collection
    {
        return self::query()
            ->where('is_active', true)
            ->where('code', '!=', $code)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }
}
