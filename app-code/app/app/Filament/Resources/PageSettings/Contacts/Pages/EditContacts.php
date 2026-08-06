<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Contacts\Pages;

use App\Filament\Resources\PageSettings\Contacts\ContactsResource;
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

class EditContacts extends EditRecord
{
    use ProcessSlugsTrait;

    protected static string $resource = ContactsResource::class;

    protected array $normalized_settings = [];

    protected array $slugs = [];

    public function getTitle(): string
    {
        return __('admin/settings/contacts_page_settings.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/settings/contacts_page_settings.navigation_label');
    }

    /**
     * @throws Throwable
     */
    public function mount(int|string|null $record = null): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapContactsPageSetting();

        parent::mount($page_setting->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PageSetting $record */
        $record = $this->getRecord();
        $settings = app(PageSettingsBootstrapService::class)->normalizeContactsSettings(
            is_array($record->settings) ? $record->settings : [],
        );

        $data = array_replace_recursive($data, $settings);
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
        $this->slugs = trim_strs_in_arr((array) Arr::get($data, 'slugs', []));
        $this->validateContactsSlugsUniqueness();
        $this->validateContactFormSettings($data);

        unset($data['slugs'], $data['meta']);

        $this->normalized_settings = app(PageSettingsBootstrapService::class)->normalizeContactsSettings($data);

        return [];
    }

    /**
     * @throws Throwable
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof PageSetting) {
            throw new LogicException('Contacts page setting record has invalid type.');
        }

        try {
            return DB::transaction(function () use ($record): Model {
                $record->update([
                    'settings' => $this->normalized_settings,
                ]);

                if ($this->updateOrCreateSlugs() === false) {
                    throw new RuntimeException('Failed to update contacts page slugs.');
                }

                return $record->fresh() ?? $record;
            });
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Failed to update contacts page settings.', [
                'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
                'record_id' => $record->id,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateContactsSlugsUniqueness(): void
    {
        /** @var PageSetting $record */
        $record = $this->getRecord();
        $used_slugs = [];

        foreach ($this->slugs as $language_id => $slug_data) {
            $slug_value = Str::trim((string) data_get($slug_data, 'name'));

            if ($slug_value === '') {
                continue;
            }

            $slug_key = ((int) $language_id) . ':' . Str::lower($slug_value);

            if (
                isset($used_slugs[$slug_key])
                || Slug::query()
                    ->where('language_id', (int) $language_id)
                    ->where('slug', $slug_value)
                    ->whereNot(function ($query) use ($record): void {
                        $query
                            ->where('sluggable_type', PageSetting::class)
                            ->where('sluggable_id', $record->id);
                    })
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    "slugs.$language_id.name" => __('admin/settings/contacts_page_settings.errors.duplicate_slug'),
                ]);
            }

            $used_slugs[$slug_key] = true;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    private function validateContactFormSettings(array $data): void
    {
        $destinations = Arr::get($data, 'contact_form.destinations', []);
        $destinations = is_array($destinations) ? $destinations : [];
        $enabled_destinations = collect($destinations)
            ->filter(fn (mixed $destination): bool => is_array($destination) && Arr::get($destination, 'enabled', false));

        $errors = [];

        if ($enabled_destinations->isEmpty()) {
            $errors['contact_form.destinations'] = __('admin/settings/contacts_page_settings.errors.destination_required');
        }

        foreach ((array) Arr::get($data, 'contact_form.fields', []) as $field_name => $field) {
            if (! is_array($field)) {
                continue;
            }

            $min_length = Arr::get($field, 'min_length');
            $max_length = Arr::get($field, 'max_length');

            if (filled($min_length) && filled($max_length) && (int) $min_length > (int) $max_length) {
                $errors["contact_form.fields.$field_name.max_length"] = __('admin/settings/contacts_page_settings.errors.max_length_less_than_min');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
