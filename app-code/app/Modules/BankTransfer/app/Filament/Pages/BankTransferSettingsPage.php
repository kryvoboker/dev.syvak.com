<?php

declare(strict_types=1);

namespace Modules\BankTransfer\Filament\Pages;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Models\ApplicationSettings\Language;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Modules\BankTransfer\Services\BankTransferSettingsService;
use Modules\BankTransfer\Support\BankTransferConfig;
use Throwable;
use UnitEnum;

final class BankTransferSettingsPage extends Page
{
    protected static ?string $slug = 'modules/bank-transfer';

    // phpcs:ignore
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingLibrary;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Modules;

    /**
     * @var array{payment_names?: array<string, string>, payment_information?: array<string, string>}
     */
    public array $settings_form = [];

    public function mount(BankTransferConfig $bank_transfer_config): void
    {
        $this->settings_form = [
            'payment_names' => $bank_transfer_config->getPaymentNames(),
            'payment_information' => $bank_transfer_config->getPaymentInformationByLocale(),
        ];
    }

    public function getTitle(): string
    {
        return __('banktransfer::admin/modules/bank_transfer.title');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): string
    {
        return __('banktransfer::admin/modules/bank_transfer.description');
    }

    public static function getNavigationLabel(): string
    {
        return __('banktransfer::admin/modules/bank_transfer.navigation_label');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('banktransfer::admin/modules/bank_transfer.sections.settings.title'))
                ->description(__('banktransfer::admin/modules/bank_transfer.sections.settings.description'))
                ->columnSpanFull()
                ->statePath('settings_form')
                ->schema([
                    $this->makeLanguageTabs($this->getActiveLanguages()),
                ])
                ->footerActions([
                    Action::make('saveSettings')
                        ->label(__('banktransfer::admin/modules/bank_transfer.actions.save_settings'))
                        ->icon(Heroicon::CheckCircle)
                        ->action(function (BankTransferSettingsService $bank_transfer_settings_service): void {
                            $this->saveSettings($bank_transfer_settings_service);
                        }),
                ]),
        ]);
    }

    public function saveSettings(BankTransferSettingsService $bank_transfer_settings_service): void
    {
        try {
            $active_languages = $this->getActiveLanguages();
            $payment_names = (array) Arr::get($this->settings_form, 'payment_names', []);
            $validation_errors = [];

            foreach ($active_languages as $language) {
                $language_code = (string) $language->code;

                if (trim((string) Arr::get($payment_names, $language_code, '')) === '') {
                    $validation_errors["settings_form.payment_names.$language_code"] = __('banktransfer::admin/modules/bank_transfer.validation.name_required', [
                        'language' => $language->name,
                    ]);
                }
            }

            if ($validation_errors !== []) {
                throw ValidationException::withMessages($validation_errors);
            }

            $settings = $bank_transfer_settings_service->save(
                $payment_names,
                (array) Arr::get($this->settings_form, 'payment_information', []),
            );

            $this->settings_form = [
                'payment_names' => $settings['payment_names'],
                'payment_information' => $settings['payment_information'],
            ];
        } catch (Throwable $throwable) {
            throw ValidationException::withMessages([
                'settings_form.payment_information' => __('banktransfer::admin/modules/bank_transfer.validation.save_failed'),
            ]);
        }

        Notification::make()
            ->title(__('admin/default.success.title'))
            ->body(__('banktransfer::admin/modules/bank_transfer.notifications.settings_saved'))
            ->success()
            ->send();
    }

    /**
     * @param Collection<int, Language> $active_languages
     */
    private function makeLanguageTabs(Collection $active_languages): Tabs
    {
        $tabs = $active_languages
            ->map(fn (Language $language): Tabs\Tab => Tabs\Tab::make($language->name)
                ->badge($language->code)
                ->schema([
                    TextInput::make("payment_names.$language->code")
                        ->label(__('banktransfer::admin/modules/bank_transfer.labels.payment_name'))
                        ->helperText(__('banktransfer::admin/modules/bank_transfer.helpers.payment_name'))
                        ->required(),
                    Textarea::make("payment_information.$language->code")
                        ->label(__('banktransfer::admin/modules/bank_transfer.labels.payment_information'))
                        ->helperText(__('banktransfer::admin/modules/bank_transfer.helpers.payment_information'))
                        ->rows(8),
                ]))
            ->all();

        return Tabs::make('BankTransferLanguageTabs')
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
