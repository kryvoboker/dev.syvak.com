<?php

declare(strict_types=1);

namespace App\Models\Settings;

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
            'socials'           => 'array',
            'contact_addresses' => 'array',
            'image_sizes'       => 'array',
        ];
    }
}
