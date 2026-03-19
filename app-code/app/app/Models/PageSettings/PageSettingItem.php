<?php

declare(strict_types=1);

namespace App\Models\PageSettings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSettingItem extends Model
{
    protected $fillable = [
        'page_setting_id',
        'type',
        'code',
        'source_type',
        'source_id',
        'is_enabled',
        'sort_order',
        'get',
        'config',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_setting_id' => 'integer',
            'source_id'       => 'integer',
            'is_enabled'      => 'boolean',
            'sort_order'      => 'integer',
            'get'             => 'array',
            'config'          => 'array',
        ];
    }

    /**
     * @return BelongsTo<PageSetting, $this>
     */
    public function pageSetting(): BelongsTo
    {
        return $this->belongsTo(PageSetting::class);
    }
}
