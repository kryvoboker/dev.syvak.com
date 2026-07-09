<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Filament\Pages;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Pages\Wiki\ModulesWikiPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Modules\NovaPoshta\Models\NovaPoshtaCity;
use Modules\NovaPoshta\Models\NovaPoshtaPoshtomat;
use Modules\NovaPoshta\Models\NovaPoshtaPostOffice;
use Modules\NovaPoshta\Models\NovaPoshtaRegion;
use Modules\NovaPoshta\Services\NovaPoshtaSyncService;
use Modules\NovaPoshta\Support\NovaPoshtaConfig;
use Throwable;
use UnitEnum;

class NovaPoshtaSyncPage extends Page
{
    protected static ?string $slug = 'modules/nova-poshta';

    // phpcs:ignore
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowPath;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Modules;

    public array $settings_form = [];

    public array $sync_state = [];

    public function mount(NovaPoshtaConfig $nova_poshta_config): void
    {
        $this->settings_form = [
            'api_key' => $nova_poshta_config->getApiKey(),
            'delivery_cost' => $nova_poshta_config->getDeliveryCost(),
            'is_delivery_cost_enabled' => $nova_poshta_config->isDeliveryCostEnabled(),
        ];
        $this->sync_state = app(NovaPoshtaSyncService::class)->getQueuedSyncState();
    }

    public function getTitle(): string
    {
        return __('novaposhta::admin/modules/nova_poshta.title');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        return __('novaposhta::admin/modules/nova_poshta.description');
    }

    public static function getNavigationLabel(): string
    {
        return __('novaposhta::admin/modules/nova_poshta.navigation_label');
    }

    public static function getNavigationSort(): ?int
    {
        return 420;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('novaposhta::admin/modules/nova_poshta.sections.shared.title'))
                    ->description(__('novaposhta::admin/modules/nova_poshta.sections.shared.description'))
                    ->columnSpanFull()
                    ->statePath('settings_form')
                    ->schema([
                        TextInput::make('api_key')
                            ->label(__('novaposhta::admin/modules/nova_poshta.labels.api_key'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText(__('novaposhta::admin/modules/nova_poshta.helpers.api_key')),
                        TextInput::make('delivery_cost')
                            ->label(__('novaposhta::admin/modules/nova_poshta.labels.delivery_cost'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->default('0.00')
                            ->step(0.01)
                            ->prefix('₴')
                            ->helperText(__('novaposhta::admin/modules/nova_poshta.helpers.delivery_cost')),
                        Toggle::make('is_delivery_cost_enabled')
                            ->label(__('novaposhta::admin/modules/nova_poshta.labels.is_delivery_cost_enabled'))
                            ->helperText(__('novaposhta::admin/modules/nova_poshta.helpers.is_delivery_cost_enabled'))
                            ->default(false),
                    ])
                    ->footerActions([
                        Action::make('saveSettings')
                            ->label(__('novaposhta::admin/modules/nova_poshta.actions.save_settings'))
                            ->icon(Heroicon::CheckCircle)
                            ->action(function (): void {
                                $this->saveSettings();
                            }),
                    ]),

                Section::make(__('novaposhta::admin/modules/nova_poshta.sections.sync.title'))
                    ->description(__('novaposhta::admin/modules/nova_poshta.sections.sync.description'))
                    ->columnSpanFull()
                    ->visible(fn (): bool => (bool) Arr::get($this->sync_state, 'is_running', false))
                    ->schema([
                        Html::make(fn (): string => $this->renderSyncWorkspace()),
                    ]),

                Section::make(__('novaposhta::admin/modules/nova_poshta.sections.summary.title'))
                    ->columnSpanFull()
                    ->schema([
                        Html::make(fn (): string => $this->renderLastSyncSummary()),
                    ]),
            ]);
    }

    /**
     * @return array|Action[]|ActionGroup[]
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncNovaPoshta')
                ->label(__('novaposhta::admin/modules/nova_poshta.actions.sync'))
                ->icon(Heroicon::ArrowPath)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('novaposhta::admin/modules/nova_poshta.actions.sync'))
                ->modalDescription(__('novaposhta::admin/modules/nova_poshta.actions.sync_confirmation'))
                ->action(function (): void {
                    try {
                        $sync_service = app(NovaPoshtaSyncService::class);

                        $this->sync_state = $sync_service->startQueuedSync();
                        $this->sync_state = $sync_service->processQueuedSyncStep();

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('novaposhta::admin/modules/nova_poshta.notifications.sync_started'))
                            ->info()
                            ->send();
                    } catch (Throwable $throwable) {
                        report($throwable);

                        Notification::make()
                            ->title(__('admin/default.errors.title'))
                            ->body(__('novaposhta::admin/modules/nova_poshta.notifications.sync_failed'))
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('stopNovaPoshtaSync')
                ->label(__('novaposhta::admin/modules/nova_poshta.actions.stop_sync'))
                ->icon(Heroicon::XMark)
                ->color('warning')
                ->visible(fn (): bool => (bool) Arr::get($this->sync_state, 'is_running', false))
                ->action(function (): void {
                    try {
                        $sync_service = app(NovaPoshtaSyncService::class);

                        $this->sync_state = $sync_service->requestQueuedSyncStop();

                        $sync_service->forgetQueuedSyncState();

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('novaposhta::admin/modules/nova_poshta.notifications.stop_requested'))
                            ->warning()
                            ->send();
                    } catch (Throwable $throwable) {
                        report($throwable);

                        Notification::make()
                            ->title(__('admin/default.errors.title'))
                            ->body(__('novaposhta::admin/modules/nova_poshta.notifications.sync_failed'))
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('openWiki')
                ->label(__('admin/wiki/wiki.actions.open_wiki'))
                ->icon(Heroicon::BookOpen)
                ->url(fn (): string => ModulesWikiPage::getUrl(), shouldOpenInNewTab: true),
        ];
    }

    /**
     * @return void
     */
    public function processSyncStep(): void
    {
        $previous_state = $this->sync_state;
        $sync_service = app(NovaPoshtaSyncService::class);

        try {
            $this->sync_state = $sync_service->processQueuedSyncStep();

            $was_running = (bool) Arr::get($previous_state, 'is_running', false);
            $is_running = (bool) Arr::get($this->sync_state, 'is_running', false);
            $stage = (string) Arr::get($this->sync_state, 'stage', '');

            if ($was_running && ! $is_running) {
                $notification = Notification::make()
                    ->title(__('admin/default.success.title'))
                    ->body(match ($stage) {
                        'completed' => __('novaposhta::admin/modules/nova_poshta.notifications.sync_completed'),
                        'stopped' => __('novaposhta::admin/modules/nova_poshta.notifications.sync_stopped'),
                        default => __('novaposhta::admin/modules/nova_poshta.notifications.sync_failed'),
                    });

                if ($stage === 'completed') {
                    $notification->success();
                } elseif ($stage === 'stopped') {
                    $notification->warning();
                } else {
                    $notification->danger();
                }

                $notification->send();
            }
        } catch (Throwable $throwable) {
            report($throwable);

            $this->sync_state = $sync_service->getQueuedSyncState();

            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('novaposhta::admin/modules/nova_poshta.notifications.sync_failed'))
                ->danger()
                ->send();
        }
    }

    /**
     * @return string
     */
    private function renderSyncWorkspace(): string
    {
        $sync_state = $this->sync_state;
        $is_running = (bool) Arr::get($sync_state, 'is_running', false);
        $stage = (string) Arr::get($sync_state, 'stage', '');
        $phase = (string) Arr::get($sync_state, 'phase', '');
        $message = (string) Arr::get($sync_state, 'message', '');

        $status_label = match ($stage) {
            'completed' => __('novaposhta::admin/modules/nova_poshta.sync.states.completed'),
            'failed' => __('novaposhta::admin/modules/nova_poshta.sync.states.failed'),
            'stopped' => __('novaposhta::admin/modules/nova_poshta.sync.states.stopped'),
            default => $is_running ? __('novaposhta::admin/modules/nova_poshta.sync.states.running') : __('novaposhta::admin/modules/nova_poshta.sync.states.idle'),
        };

        $stage_processed_rows = (int) Arr::get($sync_state, 'stage_processed_rows', 0);
        $current_stage_label = $this->getStageLabel($stage);
        $polling_attribute = $is_running ? ' wire:poll.2s="processSyncStep"' : '';
        $sync_stats = $this->getSyncStats();

        if ($is_running === false) {
            return '';
        }

        return '
            <div class="space-y-6"' . $polling_attribute . '>
                <div class="rounded-xl border p-6 shadow-sm">
                    <div class="flex flex-col gap-6">
                        <div class="max-w-3xl space-y-1">
                            <div class="flex items-center gap-3">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em]">' . e(__('novaposhta::admin/modules/nova_poshta.sync.labels.status')) . '</div>
                                <div>-</div>
                                <div class="rounded-2xl border-2 border-green-700 bg-green-600 px-2 py-1 text-2xl font-semibold">' . e($status_label) . '</div>
                            </div>
                            <p class="block text-sm leading-6">' . e($message !== '' ? $message : __('novaposhta::admin/modules/nova_poshta.sections.sync.description')) . '</p>
                        </div>

                        <div class="w-full rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="relative flex h-12 w-12 shrink-0 items-center justify-center" aria-hidden="true">
                                    <span class="absolute inset-0 rounded-full border-4 border-emerald-200 border-t-emerald-600 animate-spin"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                </div>
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="text-sm font-semibold text-emerald-950">' . e(__('novaposhta::admin/modules/nova_poshta.sync.states.running')) . '</div>
                                    <div class="text-sm text-emerald-900/80">' . e(__('novaposhta::admin/modules/nova_poshta.sync.labels.stage')) . ': ' . e($current_stage_label) . '</div>
                                    <div class="text-sm text-emerald-900/80">' . e(__('novaposhta::admin/modules/nova_poshta.sync.labels.phase')) . ': ' . e($this->getPhaseLabel($phase)) . '</div>
                                    <div class="text-sm text-emerald-900/80">' . e(__('novaposhta::admin/modules/nova_poshta.sync.labels.processed_rows')) . ': ' . e((string) $stage_processed_rows) . '</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    ' . $this->renderStatCard(__('novaposhta::admin/modules/nova_poshta.stats.regions'), ($sync_stats['regions'] ?? 0)) . '
                    ' . $this->renderStatCard(__('novaposhta::admin/modules/nova_poshta.stats.cities'), ($sync_stats['cities'] ?? 0)) . '
                    ' . $this->renderStatCard(__('novaposhta::admin/modules/nova_poshta.stats.post_offices'), ($sync_stats['post_offices'] ?? 0)) . '
                    ' . $this->renderStatCard(__('novaposhta::admin/modules/nova_poshta.stats.poshtomats'), ($sync_stats['poshtomats'] ?? 0)) . '
                </div>
            </div>
        ';
    }

    /**
     * @return array<string, int>
     */
    private function getSyncStats(): array
    {
        return [
            'regions' => NovaPoshtaRegion::query()->count(),
            'cities' => NovaPoshtaCity::query()->count(),
            'post_offices' => NovaPoshtaPostOffice::query()->count(),
            'poshtomats' => NovaPoshtaPoshtomat::query()->count(),
        ];
    }

    /**
     * @return string
     */
    private function renderLastSyncSummary(): string
    {
        $sync_stats = $this->getSyncStats();

        $rows = [
            [
                'label' => __('novaposhta::admin/modules/nova_poshta.stats.regions'),
                'value' => (int) Arr::get($sync_stats, 'regions', 0),
            ],
            [
                'label' => __('novaposhta::admin/modules/nova_poshta.stats.cities'),
                'value' => (int) Arr::get($sync_stats, 'cities', 0),
            ],
            [
                'label' => __('novaposhta::admin/modules/nova_poshta.stats.post_offices'),
                'value' => (int) Arr::get($sync_stats, 'post_offices', 0),
            ],
            [
                'label' => __('novaposhta::admin/modules/nova_poshta.stats.poshtomats'),
                'value' => (int) Arr::get($sync_stats, 'poshtomats', 0),
            ],
        ];

        $html = '<div class="overflow-hidden rounded-xl border shadow-sm">';
        $html .= '<table class="min-w-full divide-y text-sm">';
        $html .= '<thead class="border-b"><tr>';
        $html .= '<th class="px-4 py-3 text-left font-medium">' . e(__('novaposhta::admin/modules/nova_poshta.sync.labels.stage')) . '</th>';
        $html .= '<th class="px-4 py-3 text-right font-medium">' . e(__('novaposhta::admin/modules/nova_poshta.sync.labels.processed_rows')) . '</th>';
        $html .= '</tr></thead><tbody class="divide-y ">';

        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td class="px-4 py-3 ">' . e((string) $row['label']) . '</td>';
            $html .= '<td class="px-4 py-3 text-right font-medium ">' . e((string) $row['value']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * @param string $label
     * @param int    $value
     *
     * @return string
     */
    private function renderStatCard(string $label, int $value): string
    {
        return '
            <div class="rounded-lg border p-4">
                <div class="text-xs uppercase tracking-wide ">' . e($label) . '</div>
                <div class="mt-2 text-2xl font-semibold ">' . e((string) $value) . '</div>
            </div>
        ';
    }

    /**
     * @param string $stage
     *
     * @return string
     */
    private function getStageLabel(string $stage): string
    {
        return match ($stage) {
            'regions' => (string) __('novaposhta::admin/modules/nova_poshta.sync.stages.regions'),
            'cities' => (string) __('novaposhta::admin/modules/nova_poshta.sync.stages.cities'),
            'post_offices' => (string) __('novaposhta::admin/modules/nova_poshta.sync.stages.post_offices'),
            'poshtomats' => (string) __('novaposhta::admin/modules/nova_poshta.sync.stages.poshtomats'),
            default => $stage !== '' ? $stage : '—',
        };
    }

    /**
     * @return void
     */
    public function saveSettings(): void
    {
        $api_key = trim((string) Arr::get($this->settings_form, 'api_key', ''));

        if ($api_key === '') {
            throw ValidationException::withMessages([
                'settings_form.api_key' => __('novaposhta::admin/modules/nova_poshta.validation.api_key_required'),
            ]);
        }

        $delivery_cost = trim((string) Arr::get($this->settings_form, 'delivery_cost', ''));

        if ($delivery_cost === '') {
            throw ValidationException::withMessages([
                'settings_form.delivery_cost' => __('novaposhta::admin/modules/nova_poshta.validation.delivery_cost_required'),
            ]);
        }

        $is_delivery_cost_enabled = (bool) Arr::get($this->settings_form, 'is_delivery_cost_enabled', false);

        set_global_config([
            NovaPoshtaConfig::API_KEY_GLOBAL_CONFIG_KEY => [
                'value' => $api_key,
                'is_active' => true,
            ],
            NovaPoshtaConfig::DELIVERY_COST_GLOBAL_CONFIG_KEY => [
                'value' => number_format((float) $delivery_cost, 2, '.', ''),
                'is_active' => true,
            ],
            NovaPoshtaConfig::IS_DELIVERY_COST_ENABLED_GLOBAL_CONFIG_KEY => [
                'value' => $is_delivery_cost_enabled,
                'is_active' => true,
            ],
        ]);

        Notification::make()
            ->title(__('admin/default.success.title'))
            ->body(__('novaposhta::admin/modules/nova_poshta.notifications.settings_saved'))
            ->success()
            ->send();
    }

    /**
     * @param string $phase
     *
     * @return string
     */
    private function getPhaseLabel(string $phase): string
    {
        return match ($phase) {
            'collect' => (string) __('novaposhta::admin/modules/nova_poshta.sync.phases.collect'),
            'finalize' => (string) __('novaposhta::admin/modules/nova_poshta.sync.phases.finalize'),
            'completed' => (string) __('novaposhta::admin/modules/nova_poshta.sync.states.completed'),
            'failed' => (string) __('novaposhta::admin/modules/nova_poshta.sync.states.failed'),
            default => $phase !== '' ? $phase : '—',
        };
    }
}
