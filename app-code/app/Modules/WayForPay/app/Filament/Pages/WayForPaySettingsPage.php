<?php

declare(strict_types=1);

namespace Modules\WayForPay\Filament\Pages;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Models\ApplicationSettings\Language;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Modules\WayForPay\Services\Filament\WayForPaySettingsService;
use Modules\WayForPay\Support\WayForPayConfig;
use Throwable;
use UnitEnum;

final class WayForPaySettingsPage extends Page
{
    protected static ?string $slug = 'modules/wayforpay';

    // phpcs:ignore
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Modules;

    /**
     * @var array{settings?: array<string, string>, payment_names?: array<string, string>}
     */
    public array $settings_form = [];

    public function mount(WayForPayConfig $wayforpay_config): void
    {
        $this->settings_form = [
            'settings' => $wayforpay_config->getSettings(),
            'payment_names' => $wayforpay_config->getPaymentNames(),
        ];
    }

    public function getTitle(): string
    {
        return __('wayforpay::admin/modules/wayforpay.title');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveSettings')
                ->label(__('wayforpay::admin/modules/wayforpay.actions.save_settings'))
                ->icon(Heroicon::CheckCircle)
                ->action(function (WayForPaySettingsService $wayforpay_settings_service): void {
                    $this->saveSettings($wayforpay_settings_service);
                }),
        ];
    }

    public function getSubheading(): string
    {
        return __('wayforpay::admin/modules/wayforpay.description');
    }

    public static function getNavigationLabel(): string
    {
        return __('wayforpay::admin/modules/wayforpay.navigation_label');
    }

    public function content(Schema $schema): Schema
    {
        $wayforpay_config = app(WayForPayConfig::class);

        return $schema->components([
            Section::make(__('wayforpay::admin/modules/wayforpay.sections.names.title'))
                ->description(__('wayforpay::admin/modules/wayforpay.sections.names.description'))
                ->columnSpanFull()
                ->statePath('settings_form')
                ->schema([$this->makeLanguageTabs($this->getActiveLanguages())]),

            Section::make(__('wayforpay::admin/modules/wayforpay.sections.credentials.title'))
                ->description(__('wayforpay::admin/modules/wayforpay.sections.credentials.description'))
                ->columnSpanFull()
                ->statePath('settings_form')
                ->schema([
                    TextInput::make('settings.merchant_account')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.merchant_account'))
                        ->required(),
                    TextInput::make('settings.secret_key')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.secret_key'))
                        ->helperText(__('wayforpay::admin/modules/wayforpay.helpers.secret_key'))
                        ->password()
                        ->revealable()
                        ->required(),
                    TextInput::make('settings.merchant_domain_name')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.merchant_domain_name'))
                        ->required()
                        ->maxLength(255),
                    Toggle::make('settings.checkout_widget_enabled')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.checkout_widget_enabled'))
                        ->helperText(__('wayforpay::admin/modules/wayforpay.helpers.checkout_widget_enabled'))
                        ->default($wayforpay_config->getDefaultBoolean('checkout_widget_enabled', true)),
                    Select::make('settings.merchant_auth_type')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.merchant_auth_type'))
                        ->options(array_combine(
                            $wayforpay_config->getOptionList('merchant_auth_types'),
                            $wayforpay_config->getOptionList('merchant_auth_types'),
                        ))
                        ->default($wayforpay_config->getDefault('merchant_auth_type'))
                        ->required(),
                    Select::make('settings.merchant_transaction_type')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.merchant_transaction_type'))
                        ->options(array_combine(
                            $wayforpay_config->getOptionList('transaction_types'),
                            $wayforpay_config->getOptionList('transaction_types'),
                        ))
                        ->default($wayforpay_config->getDefault('merchant_transaction_type'))
                        ->required(),
                    Select::make('settings.merchant_transaction_secure_type')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.merchant_transaction_secure_type'))
                        ->options(array_combine(
                            $wayforpay_config->getOptionList('secure_transaction_types'),
                            $wayforpay_config->getOptionList('secure_transaction_types'),
                        ))
                        ->default($wayforpay_config->getDefault('merchant_transaction_secure_type'))
                        ->required(),
                    Select::make('settings.api_version')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.api_version'))
                        ->options(array_combine(
                            $wayforpay_config->getOptionList('api_versions'),
                            $wayforpay_config->getOptionList('api_versions'),
                        ))
                        ->default($wayforpay_config->getDefault('api_version'))
                        ->required(),
                    Select::make('settings.language')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.language'))
                        ->options(array_combine(
                            $wayforpay_config->getAllowedLanguages(),
                            $wayforpay_config->getAllowedLanguages(),
                        ))
                        ->default($wayforpay_config->getDefault('language'))
                        ->required(),
                    TextInput::make('settings.payment_systems')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.payment_systems'))
                        ->helperText(__('wayforpay::admin/modules/wayforpay.helpers.payment_systems')),
                    TextInput::make('settings.callback_handler_method')
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.callback_handler_method'))
                        ->helperText(__('wayforpay::admin/modules/wayforpay.helpers.callback_handler_method'))
                        ->default($wayforpay_config->getCallbackHandlerMethod())
                        ->readOnly()
                        ->dehydrated(false),
                ])
                ->footerActions([
                    Action::make('saveSettings')
                        ->label(__('wayforpay::admin/modules/wayforpay.actions.save_settings'))
                        ->icon(Heroicon::CheckCircle)
                        ->action(function (WayForPaySettingsService $wayforpay_settings_service): void {
                            $this->saveSettings($wayforpay_settings_service);
                        }),
                ]),
        ]);
    }

    public function saveSettings(WayForPaySettingsService $wayforpay_settings_service): void
    {
        try {
            $wayforpay_config = app(WayForPayConfig::class);
            $payment_names = (array)Arr::get($this->settings_form, 'payment_names', []);
            $validation_errors = [];

            foreach ($this->getActiveLanguages() as $language) {
                $language_code = (string)$language->code;

                if (trim((string)Arr::get($payment_names, $language_code, '')) === '') {
                    $validation_errors["settings_form.payment_names.$language_code"] = __('wayforpay::admin/modules/wayforpay.validation.name_required', [
                        'language' => $language->name,
                    ]);
                }
            }

            if ($validation_errors !== []) {
                throw ValidationException::withMessages($validation_errors);
            }

            $settings = (array)Arr::get($this->settings_form, 'settings', []);
            $existing_settings = app(WayForPayConfig::class)->getSettings();

            if (blank($settings['secret_key'] ?? null)) {
                $settings['secret_key'] = $existing_settings['secret_key'] ?? '';
            }

            if (blank($settings['merchant_transaction_type'] ?? null)) {
                $settings['merchant_transaction_type'] = $wayforpay_config->getDefault('merchant_transaction_type');
            }

            if (blank($settings['merchant_transaction_secure_type'] ?? null)) {
                $settings['merchant_transaction_secure_type'] = $wayforpay_config->getDefault('merchant_transaction_secure_type');
            }

            if (blank($settings['api_version'] ?? null)) {
                $settings['api_version'] = $wayforpay_config->getDefault('api_version');
            }

            if (blank($settings['merchant_auth_type'] ?? null)) {
                $settings['merchant_auth_type'] = $wayforpay_config->getDefault('merchant_auth_type');
            }

            if (blank($settings['language'] ?? null)) {
                $settings['language'] = $wayforpay_config->getDefault('language');
            }

            if (!array_key_exists('checkout_widget_enabled', $settings)) {
                $settings['checkout_widget_enabled'] = $wayforpay_config->getDefaultBoolean('checkout_widget_enabled', true);
            }

            unset($settings['callback_handler_method']);

            $invalid_payment_systems = array_diff(
                string_to_array((string)($settings['payment_systems'] ?? ''), ';'),
                $wayforpay_config->getOptionList('payment_systems'),
            );

            if ($invalid_payment_systems !== []) {
                throw ValidationException::withMessages([
                    'settings_form.settings.payment_systems' => __('wayforpay::admin/modules/wayforpay.validation.payment_systems_invalid', [
                        'systems' => implode(', ', $invalid_payment_systems),
                    ]),
                ]);
            }

            $saved_settings = $wayforpay_settings_service->save($settings, $payment_names);
            $this->settings_form = $saved_settings;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw ValidationException::withMessages([
                'settings_form.settings.merchant_account' => __('wayforpay::admin/modules/wayforpay.validation.save_failed'),
            ]);
        }

        Notification::make()
            ->title(__('admin/default.success.title'))
            ->body(__('wayforpay::admin/modules/wayforpay.notifications.settings_saved'))
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
                        ->label(__('wayforpay::admin/modules/wayforpay.labels.payment_name'))
                        ->helperText(__('wayforpay::admin/modules/wayforpay.helpers.payment_name'))
                        ->required(),
                ]))
            ->all();

        return Tabs::make('WayForPayLanguageTabs')
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
