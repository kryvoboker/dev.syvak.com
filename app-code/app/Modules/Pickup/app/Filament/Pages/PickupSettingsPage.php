<?php

declare(strict_types=1);

namespace Modules\Pickup\Filament\Pages;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Models\ApplicationSettings\Language;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Pickup\Services\Filament\PickupSettingsService;
use Modules\Pickup\Support\PickupConfig;
use Throwable;
use UnitEnum;

final class PickupSettingsPage extends Page
{
    protected static ?string $slug = 'modules/pickup-store';

    // phpcs:ignore
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Modules;

    /**
     * @var array{addresses?: array<string, string>, map_iframe?: string}
     */
    public array $settings_form = [];

    public function mount(PickupConfig $pickup_config): void
    {
        $this->settings_form = [
            'addresses' => $pickup_config->getAddresses(),
            'map_iframe' => $pickup_config->getMapIframe(),
        ];
    }

    public function getTitle(): string
    {
        return __('pickup::admin/modules/pickup.title');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): string
    {
        return __('pickup::admin/modules/pickup.description');
    }

    public static function getNavigationLabel(): string
    {
        return __('pickup::admin/modules/pickup.navigation_label');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('pickup::admin/modules/pickup.sections.settings.title'))
                ->description(__('pickup::admin/modules/pickup.sections.settings.description'))
                ->columnSpanFull()
                ->statePath('settings_form')
                ->schema([
                    $this->makeLanguageTabs($this->getActiveLanguages()),
                    Textarea::make('map_iframe')
                        ->label(__('pickup::admin/modules/pickup.labels.map_iframe'))
                        ->helperText(__('pickup::admin/modules/pickup.helpers.map_iframe'))
                        ->rows(6),
                ])
                ->footerActions([
                    Action::make('saveSettings')
                        ->label(__('pickup::admin/modules/pickup.actions.save_settings'))
                        ->icon(Heroicon::CheckCircle)
                        ->action(function (PickupSettingsService $pickup_settings_service): void {
                            $this->saveSettings($pickup_settings_service);
                        }),
                ]),
        ]);
    }

    public function saveSettings(PickupSettingsService $pickup_settings_service): void
    {
        $active_languages = $this->getActiveLanguages();
        $addresses = (array) Arr::get($this->settings_form, 'addresses', []);
        $validation_errors = [];

        foreach ($active_languages as $language) {
            $language_code = (string) $language->code;

            if (trim((string) Arr::get($addresses, $language_code, '')) === '') {
                $validation_errors["settings_form.addresses.$language_code"] = __('pickup::admin/modules/pickup.validation.address_required', [
                    'language' => $language->name,
                ]);
            }
        }

        if ($validation_errors !== []) {
            throw ValidationException::withMessages($validation_errors);
        }

        try {
            $this->settings_form = [
                'addresses' => collect($addresses)
                    ->mapWithKeys(fn (mixed $address, mixed $language_code): array => [
                        strtolower(trim((string) $language_code)) => trim((string) $address),
                    ])
                    ->all(),
                'map_iframe' => (string) Arr::get($this->settings_form, 'map_iframe', ''),
            ];

            $pickup_settings_service->save(
                $this->settings_form['addresses'],
                $this->settings_form['map_iframe'],
            );
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[PickupSettingsPage.saveSettings] settings save failed', [
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'settings_form.map_iframe' => __('pickup::admin/modules/pickup.validation.map_iframe_invalid'),
            ]);
        }

        Notification::make()
            ->title(__('admin/default.success.title'))
            ->body(__('pickup::admin/modules/pickup.notifications.settings_saved'))
            ->success()
            ->send();
    }

    /**
     * @param  Collection<int, Language>  $active_languages
     */
    private function makeLanguageTabs(Collection $active_languages): Tabs
    {
        $tabs = $active_languages
            ->map(fn (Language $language): Tabs\Tab => Tabs\Tab::make($language->name)
                ->badge($language->code)
                ->schema([
                    Textarea::make("addresses.$language->code")
                        ->label(__('pickup::admin/modules/pickup.labels.store_address'))
                        ->helperText(__('pickup::admin/modules/pickup.helpers.store_address'))
                        ->rows(4)
                        ->required(),
                ]))
            ->all();

        return Tabs::make('PickupLanguageTabs')
            ->tabs($tabs)
            ->activeTab(1)
            ->contained(false)
            ->columnSpanFull();
    }

    /**
     * @return Collection<int, Language>
     */
    private function getActiveLanguages(): Collection
    {
        return (new Language())->getActiveLanguages();
    }
}
