<?php

declare(strict_types=1);

namespace App\Models\Settings\PageSettings;

use App\Models\Settings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSettingTranslation extends Model
{
    protected $fillable = [
        'page_setting_id',
        'language_id',
        'content',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_setting_id' => 'integer',
            'language_id'     => 'integer',
            'content'         => 'array',
        ];
    }

    /**
     * @return BelongsTo<PageSetting, $this>
     */
    public function pageSetting(): BelongsTo
    {
        return $this->belongsTo(PageSetting::class);
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
