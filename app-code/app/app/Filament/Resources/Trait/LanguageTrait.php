<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trait;

use App\Models\ApplicationSettings\Language;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

trait LanguageTrait
{
    protected static function getCurrentLanguageId(): ?int
    {
        $language = new Language();

        // Get current locale language ID (adjust based on your logic)
        $current_language = $language->getLanguageByCode(app()->getLocale());

        if ($current_language === null) {
            return null;
        }

        return (int) $current_language->id;
    }

    protected static function validateLanguageIdIsNotNull(?int $language_id, ?string $returned_value = null): ?string
    {
        if ($language_id === null) {
            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/default.errors.no_language'))
                ->danger()
                ->send();

            return $returned_value ?? '-';
        }

        return null;
    }

    /**
     * @return Collection<int, Language>
     */
    protected static function getActiveLanguages(): Collection
    {
        return (new Language())->getActiveLanguages();
    }

    /**
     * @param  Collection<int, Language>  $active_languages
     */
    protected static function tryGetCurrentLanguageIdFromActiveLangs(Collection $active_languages): ?int
    {
        $language = $active_languages->where('is_default', true)->first();

        if (! $language instanceof Language) {
            return null;
        }

        return (int) $language->id;
    }
}
