<?php

declare(strict_types=1);

namespace App\Models\ApplicationSettings;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    /** @var array<string, mixed>|null */
    protected $user_settings;

    /** @var array<string, mixed>|null */
    protected $system_settings;

    /** @var array<string, mixed>|null */
    protected $ai_settings;

    protected $fillable = [
        'titles',
        'meta_titles',
        'meta_descriptions',
        'meta_keywords',
        'socials',
        'timezone',
        'image_sizes',
        'system_settings',
        'user_settings',
        'ai_settings',
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'titles' => 'array',
            'meta_titles' => 'array',
            'meta_descriptions' => 'array',
            'meta_keywords' => 'array',
            'socials' => 'array',
            'image_sizes' => 'array',
            'system_settings' => 'array',
            'user_settings' => 'array',
            'ai_settings' => 'array',
        ];
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function titles(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function metaTitles(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function metaDescriptions(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function metaKeywords(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function workTime(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function contactAddresses(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function aiSettings(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function systemSettings(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function userSettings(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    public function getAppSettings(): ?self
    {
        return self::first();
    }
}
