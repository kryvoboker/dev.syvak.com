<?php

declare(strict_types=1);

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'titles',
        'meta_titles',
        'meta_descriptions',
        'meta_keywords',
        'contact_emails',
        'contact_phones',
        'socials',
        'work_time',
        'contact_addresses',
        'coordinates',
        'iframe_map',
        'timezone',
        'image_sizes',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'titles'            => 'array',
            'meta_titles'       => 'array',
            'meta_descriptions' => 'array',
            'meta_keywords'     => 'array',
            'contact_emails'    => 'array',
            'contact_phones'    => 'array',
            'socials'           => 'array',
            'work_time'         => 'array',
            'contact_addresses' => 'array',
            'image_sizes'       => 'array',
        ];
    }

    /**
     * @return Attribute
     */
    public function titles(): Attribute
    {
        return Attribute::make(
            set: fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @return Attribute
     */
    public function metaTitles(): Attribute
    {
        return Attribute::make(
            set: fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @return Attribute
     */
    public function metaDescriptions(): Attribute
    {
        return Attribute::make(
            set: fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @return Attribute
     */
    public function metaKeywords(): Attribute
    {
        return Attribute::make(
            set: fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @return Attribute
     */
    public function workTime(): Attribute
    {
        return Attribute::make(
            set: fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @return Attribute
     */
    public function contactAddresses(): Attribute
    {
        return Attribute::make(
            set: fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }
}
