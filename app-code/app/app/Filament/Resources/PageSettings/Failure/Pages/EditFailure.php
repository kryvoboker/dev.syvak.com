<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Failure\Pages;

use App\Filament\Resources\PageSettings\Failure\FailureResource;
use App\Filament\Resources\Trait\ProcessSlugsTrait;
use App\Models\PageSettings\PageSetting;
use App\Models\Slug;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use Throwable;

class EditFailure extends EditRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = FailureResource::class;

    protected array $failure_settings = [];

    protected array $slugs = [];

    public int|string|Model|null $record = null;

    public function getTitle(): string
    {
        return __('admin/settings/failure_page_settings.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/settings/failure_page_settings.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(function (): void {
                    $this->save();
                }),
        ];
    }

    public function mount(int|string|null $record = null): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapFailurePageSetting();

        parent::mount($page_setting->id);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PageSetting $record */
        $record = $this->getRecord();
        $settings = is_array($record->settings) ? $record->settings : [];

        $data['localized_content'] = Arr::get($settings, 'localized', []);
        $data['images'] = Arr::get($settings, 'images', []);
        $data['buttons'] = Arr::get($settings, 'buttons', []);
        $data['support_contacts'] = Arr::get($settings, 'support_contacts', []);
        $this->getSlugs($data);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @throws ValidationException
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->failure_settings = [
            'localized' => $this->normalizeLocalizedContent((array) Arr::get($data, 'localized_content', [])),
            'images' => (array) Arr::get($data, 'images', []),
            'buttons' => (array) Arr::get($data, 'buttons', []),
            'support_contacts' => (array) Arr::get($data, 'support_contacts', []),
        ];
        $this->slugs = trim_strs_in_arr((array) Arr::get($data, 'slugs', []));
        $this->validateFailureSlugsUniqueness();

        unset($data['localized_content'], $data['images'], $data['buttons'], $data['support_contacts'], $data['slugs']);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @throws Throwable
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof PageSetting) {
            throw new LogicException('Failure page setting record has invalid type.');
        }

        try {
            return DB::transaction(function () use ($record): Model {
                $record->update([
                    'settings' => app(PageSettingsBootstrapService::class)->normalizeFailureSettings($this->failure_settings),
                ]);

                if ($this->updateOrCreateSlugs() === false) {
                    throw new RuntimeException('Failed to update failure page slugs.');
                }

                return $record->fresh() ?? $record;
            });
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Failed to update failure page settings.', [
                'page_type' => PageSetting::PAGE_TYPE_FAILURE,
                'record_id' => $record->id,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $localized_content
     * @return array<string, array<string, mixed>>
     */
    private function normalizeLocalizedContent(array $localized_content): array
    {
        return collect($localized_content)
            ->filter(fn (mixed $content): bool => is_array($content))
            ->map(function (array $content): array {
                return [
                    'title' => Str::trim((string) Arr::get($content, 'title', '')),
                    'description' => filled(Arr::get($content, 'description'))
                        ? Str::trim((string) Arr::get($content, 'description'))
                        : null,
                    'retry_button' => [
                        'label' => Str::trim((string) Arr::get($content, 'retry_button.label', '')),
                    ],
                    'alternative_payment_button' => [
                        'label' => Str::trim((string) Arr::get($content, 'alternative_payment_button.label', '')),
                    ],
                    'working_hours' => filled(Arr::get($content, 'working_hours'))
                        ? Str::trim((string) Arr::get($content, 'working_hours'))
                        : null,
                ];
            })
            ->all();
    }

    /**
     * @throws ValidationException
     */
    private function validateFailureSlugsUniqueness(): void
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
                    "slugs.$language_id.name" => __('admin/settings/failure_page_settings.errors.duplicate_slug'),
                ]);
            }

            $used_slugs[$slug_key] = true;
        }
    }
}
