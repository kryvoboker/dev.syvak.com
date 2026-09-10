<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trait;

use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Products\Product;
use App\Models\Infos\InfoPage;
use App\Models\PageSettings\PageSetting;
use App\Models\Slug;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LogicException;

trait ProcessSlugsTrait
{
    protected function updateOrCreateSlugs(): bool
    {
        $record = $this->getSluggableRecord();

        foreach ($this->slugs as $language_id => $slug_data) {
            $slug_value = Str::trim((string) ($slug_data['name'] ?? ''));

            if ($slug_value === '') {
                $record->slugs()->where('language_id', (int) $language_id)->delete();

                continue;
            }

            try {
                $record->slugs()->updateOrCreate(
                    ['language_id' => (int) $language_id],
                    ['slug' => $slug_value],
                );
            } catch (Exception $e) {
                Log::channel('stack')->error($e->getMessage());

                Notification::make()
                    ->title(__('admin/default.errors.title'))
                    ->body(__('admin/default.errors.create_or_update_slugs_failed'))
                    ->danger()
                    ->send();

                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $data */
    protected function getSlugs(array &$data): void
    {
        $record = $this->getSluggableRecord();

        // Load slugs
        $slugs = $record->slugs()
            ->get()
            ->keyBy('language_id')
            ->map(fn (Slug $slug): array => [
                'language_id' => $slug->language_id,
                'name' => $slug->slug,
            ])
            ->toArray();

        $data['slugs'] = $slugs;
    }

    private function getSluggableRecord(): Category|Product|InfoPage|PageSetting
    {
        $record = $this->record ?? null;

        if ($record instanceof Category || $record instanceof Product || $record instanceof InfoPage || $record instanceof PageSetting) {
            return $record;
        }

        throw new LogicException('Record does not support slugs relation.');
    }
}
