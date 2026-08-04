<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\NotFound\Pages;

use App\Filament\Resources\PageSettings\NotFound\NotFoundResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\PageSettings\PageSetting;
use App\Models\Slug;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditNotFound extends EditRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = NotFoundResource::class;

    protected array $localized_content = [];

    protected array $images = [];

    protected array $slugs = [];

    public int|string|Model|null $record = null;

    public function getTitle(): string
    {
        return __('admin/settings/not_found_page_settings.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/settings/not_found_page_settings.navigation_label');
    }

    /**
     * @throws Throwable
     */
    public function mount(int|string|null $record = null): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapNotFoundPageSetting();

        parent::mount($page_setting->id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PageSetting $record */
        $record = $this->getRecord();
        $settings = is_array($record->settings) ? $record->settings : [];

        $data['localized_content'] = Arr::get($settings, 'localized', []);
        $data['images'] = Arr::get($settings, 'images', []);
        $this->getSlugs($data);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->localized_content = $this->normalizeLocalizedContent((array) Arr::get($data, 'localized_content', []));
        $this->images = (array) Arr::get($data, 'images', []);
        $this->slugs = trim_strs_in_arr((array) Arr::get($data, 'slugs', []));
        $this->validateNotFoundSlugsUniqueness();

        unset($data['localized_content'], $data['images'], $data['slugs']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof PageSetting) {
            throw new \LogicException('Not found page setting record has invalid type.');
        }

        try {
            return DB::transaction(function () use ($record): Model {
                $settings = is_array($record->settings) ? $record->settings : [];
                $settings['localized'] = $this->localized_content;
                $settings['images'] = $this->images;

                $record->update([
                    'settings' => app(PageSettingsBootstrapService::class)->normalizeNotFoundSettings($settings),
                ]);

                if ($this->updateOrCreateSlugs() === false) {
                    throw new \RuntimeException('Failed to update not found page slugs.');
                }

                return $record->fresh() ?? $record;
            });
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Failed to update not found page settings.', [
                'page_type' => PageSetting::PAGE_TYPE_NOT_FOUND,
                'record_id' => $record->id,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @param  array<string, mixed>  $localized_content
     * @return array<string, array<string, mixed>>
     */
    private function normalizeLocalizedContent(array $localized_content): array
    {
        return collect($localized_content)
            ->filter(fn (mixed $content): bool => is_array($content))
            ->map(function (array $content): array {
                $link = is_array($content['link'] ?? null) ? $content['link'] : [];

                return [
                    'title' => Str::trim((string) ($content['title'] ?? '')),
                    'description' => filled($content['description'] ?? null)
                        ? Str::trim((string) $content['description'])
                        : null,
                    'link' => [
                        'label' => filled($link['label'] ?? null)
                            ? Str::trim((string) $link['label'])
                            : null,
                        'url' => Str::trim((string) ($link['url'] ?? '')),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @throws ValidationException
     */
    private function validateNotFoundSlugsUniqueness(): void
    {
        /** @var PageSetting $record */
        $record = $this->getRecord();
        $used_slugs = [];

        foreach ($this->slugs as $language_id => $slug_data) {
            $slug_value = Str::trim((string) data_get($slug_data, 'name'));

            if ($slug_value === '') {
                continue;
            }

            $slug_key = ((int) $language_id) . ':' . mb_strtolower($slug_value);

            if (isset($used_slugs[$slug_key]) || Slug::query()
                ->where('language_id', (int) $language_id)
                ->where('slug', $slug_value)
                ->whereNot(function ($query) use ($record): void {
                    $query
                        ->where('sluggable_type', PageSetting::class)
                        ->where('sluggable_id', $record->id);
                })
                ->exists()) {
                throw ValidationException::withMessages([
                    "slugs.$language_id.name" => __('admin/settings/not_found_page_settings.errors.duplicate_slug'),
                ]);
            }

            $used_slugs[$slug_key] = true;
        }
    }
}
