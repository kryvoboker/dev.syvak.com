<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Filament\Pages;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Pages\Wiki\ModulesWikiPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
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
        return __('admin/modules/nova_poshta.title');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        return __('admin/modules/nova_poshta.description');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/modules/nova_poshta.navigation_label');
    }

    public static function getNavigationSort(): ?int
    {
        return 420;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/modules/nova_poshta.sections.shared.title'))
                    ->description(__('admin/modules/nova_poshta.sections.shared.description'))
                    ->columnSpanFull()
                    ->statePath('settings_form')
                    ->schema([
                        TextInput::make('api_key')
                            ->label(__('admin/modules/nova_poshta.labels.api_key'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText(__('admin/modules/nova_poshta.helpers.api_key')),
                        TextInput::make('delivery_cost')
                            ->label(__('admin/modules/nova_poshta.labels.delivery_cost'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->default('0.00')
                            ->step(0.01)
                            ->prefix('₴')
                            ->helperText(__('admin/modules/nova_poshta.helpers.delivery_cost')),
                        Toggle::make('is_delivery_cost_enabled')
                            ->label(__('admin/modules/nova_poshta.labels.is_delivery_cost_enabled'))
                            ->helperText(__('admin/modules/nova_poshta.helpers.is_delivery_cost_enabled'))
                            ->default(false),
                    ])
                    ->footerActions([
                        Action::make('saveSettings')
                            ->label(__('admin/modules/nova_poshta.actions.save_settings'))
                            ->icon(Heroicon::CheckCircle)
                            ->action(function (): void {
                                $this->saveSettings();
                            }),
                    ]),

                Section::make(__('admin/modules/nova_poshta.sections.sync.title'))
                    ->description(__('admin/modules/nova_poshta.sections.sync.description'))
                    ->columnSpanFull()
                    ->visible(fn (): bool => (bool) Arr::get($this->sync_state, 'is_running', false))
                    ->schema([
                        Html::make(fn (): string => $this->renderSyncWorkspace()),
                    ]),

                Section::make(__('admin/modules/nova_poshta.sections.summary.title'))
                    ->columnSpanFull()
                    ->schema([
                        Html::make(fn (): string => $this->renderLastSyncSummary()),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncNovaPoshta')
                ->label(__('admin/modules/nova_poshta.actions.sync'))
                ->icon(Heroicon::ArrowPath)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('admin/modules/nova_poshta.actions.sync'))
                ->modalDescription(__('admin/modules/nova_poshta.actions.sync_confirmation'))
                ->action(function (): void {
                    try {
                        $sync_service = app(NovaPoshtaSyncService::class);

                        $this->sync_state = $sync_service->startQueuedSync();
                        $this->sync_state = $sync_service->processQueuedSyncStep();

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/modules/nova_poshta.notifications.sync_started'))
                            ->info()
                            ->send();
                    } catch (Throwable $throwable) {
                        report($throwable);

                        Notification::make()
                            ->title(__('admin/default.errors.title'))
                            ->body(__('admin/modules/nova_poshta.notifications.sync_failed'))
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('stopNovaPoshtaSync')
                ->label(__('admin/modules/nova_poshta.actions.stop_sync'))
                ->icon(Heroicon::XMark)
                ->color('warning')
                ->visible(fn (): bool => (bool) Arr::get($this->sync_state, 'is_running', false))
                ->action(function (): void {
                    try {
                        $sync_service = app(NovaPoshtaSyncService::class);

                        $this->sync_state = $sync_service->requestQueuedSyncStop();

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/modules/nova_poshta.notifications.stop_requested'))
                            ->warning()
                            ->send();
                    } catch (Throwable $throwable) {
                        report($throwable);

                        Notification::make()
                            ->title(__('admin/default.errors.title'))
                            ->body(__('admin/modules/nova_poshta.notifications.sync_failed'))
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
                        'completed' => __('admin/modules/nova_poshta.notifications.sync_completed'),
                        'stopped' => __('admin/modules/nova_poshta.notifications.sync_stopped'),
                        default => __('admin/modules/nova_poshta.notifications.sync_failed'),
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
                ->body(__('admin/modules/nova_poshta.notifications.sync_failed'))
                ->danger()
                ->send();
        }
    }

    private function renderSyncWorkspace(): string
    {
        $sync_state = $this->sync_state;
        $is_running = (bool) Arr::get($sync_state, 'is_running', false);
        $overall_progress = max(0, min(100, (int) Arr::get($sync_state, 'overall_progress', 0)));
        $stage_progress = max(0, min(100, (int) Arr::get($sync_state, 'stage_progress', 0)));
        $stage = (string) Arr::get($sync_state, 'stage', '');
        $phase = (string) Arr::get($sync_state, 'phase', '');
        $message = (string) Arr::get($sync_state, 'message', '');

        $status_label = match ($stage) {
            'completed' => __('admin/modules/nova_poshta.sync.states.completed'),
            'failed' => __('admin/modules/nova_poshta.sync.states.failed'),
            'stopped' => __('admin/modules/nova_poshta.sync.states.stopped'),
            default => $is_running ? __('admin/modules/nova_poshta.sync.states.running') : __('admin/modules/nova_poshta.sync.states.idle'),
        };

        $current_page = (int) Arr::get($sync_state, 'current_page', 1);
        $total_pages = (int) Arr::get($sync_state, 'total_pages', 1);
        $stage_total_rows = (int) Arr::get($sync_state, 'stage_total_rows', 0);
        $stage_processed_rows = (int) Arr::get($sync_state, 'stage_processed_rows', 0);
        $current_stage_label = $this->getStageLabel($stage);
        $polling_attribute = $is_running ? ' wire:poll.2s="processSyncStep"' : '';
        $sync_stats = $this->getSyncStats();

        if ($is_running === false) {
            return '';
        }

        return '
            <div class="space-y-6"' . $polling_attribute . '>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">' . e(__('admin/modules/nova_poshta.sync.labels.status')) . '</div>
                            <div class="mt-2 text-2xl font-semibold text-gray-900">' . e($status_label) . '</div>
                            <p class="mt-3 text-sm leading-6 text-gray-600">' . e($message !== '' ? $message : __('admin/modules/nova_poshta.sections.sync.description')) . '</p>
                        </div>

                        <div class="w-full max-w-xl rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="font-medium text-gray-700">' . e(__('admin/modules/nova_poshta.sync.labels.overall_progress')) . '</span>
                                <span class="font-semibold text-gray-900">' . e((string) $overall_progress) . '%</span>
                            </div>
                            <div class="mt-3 h-3 overflow-hidden rounded-full bg-gray-200">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" style="width: ' . e((string) $overall_progress) . '%;"></div>
                            </div>
                            <div class="mt-4 grid gap-2 text-sm text-gray-600">
                                <div><span class="font-medium text-gray-700">' . e(__('admin/modules/nova_poshta.sync.labels.stage')) . ':</span> ' . e($current_stage_label) . '</div>
                                <div><span class="font-medium text-gray-700">' . e(__('admin/modules/nova_poshta.sync.labels.phase')) . ':</span> ' . e($this->getPhaseLabel($phase)) . '</div>
                                <div><span class="font-medium text-gray-700">' . e(__('admin/modules/nova_poshta.sync.labels.page')) . ':</span> ' . e($current_page . ' / ' . $total_pages) . '</div>
                                <div><span class="font-medium text-gray-700">' . e(__('admin/modules/nova_poshta.sync.labels.processed_rows')) . ':</span> ' . e($stage_processed_rows . ' / ' . $stage_total_rows) . '</div>
                                <div><span class="font-medium text-gray-700">' . e(__('admin/modules/nova_poshta.sync.labels.stage_progress')) . ':</span> ' . e((string) $stage_progress) . '%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    ' . $this->renderStatCard(__('admin/modules/nova_poshta.stats.regions'), ($sync_stats['regions'] ?? 0)) . '
                    ' . $this->renderStatCard(__('admin/modules/nova_poshta.stats.cities'), ($sync_stats['cities'] ?? 0)) . '
                    ' . $this->renderStatCard(__('admin/modules/nova_poshta.stats.post_offices'), ($sync_stats['post_offices'] ?? 0)) . '
                    ' . $this->renderStatCard(__('admin/modules/nova_poshta.stats.poshtomats'), ($sync_stats['poshtomats'] ?? 0)) . '
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
     * @return array<string, mixed>
     */
    private function getLastSyncSummary(): array
    {
        $last_sync_summary = Cache::get('nova_poshta.last_sync_summary', []);

        return is_array($last_sync_summary) ? $last_sync_summary : [];
    }

    private function renderLastSyncSummary(): string
    {
        $last_sync_summary = $this->getLastSyncSummary();

        if ($last_sync_summary === []) {
            return '<p class="text-sm text-gray-600">' . e(__('admin/modules/nova_poshta.sections.summary.empty')) . '</p>';
        }

        $rows = [
            [
                'label' => __('admin/modules/nova_poshta.stats.regions'),
                'value' => (int) data_get($last_sync_summary, 'regions.imported', 0),
            ],
            [
                'label' => __('admin/modules/nova_poshta.stats.cities'),
                'value' => (int) data_get($last_sync_summary, 'cities.imported', 0),
            ],
            [
                'label' => __('admin/modules/nova_poshta.stats.post_offices'),
                'value' => (int) data_get($last_sync_summary, 'post_offices.imported', 0),
            ],
            [
                'label' => __('admin/modules/nova_poshta.stats.poshtomats'),
                'value' => (int) data_get($last_sync_summary, 'poshtomats.imported', 0),
            ],
        ];

        $html = '<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">';
        $html .= '<table class="min-w-full divide-y divide-gray-200 text-sm">';
        $html .= '<thead class="bg-gray-50"><tr>';
        $html .= '<th class="px-4 py-3 text-left font-medium text-gray-600">' . e(__('admin/modules/nova_poshta.sync.labels.stage')) . '</th>';
        $html .= '<th class="px-4 py-3 text-right font-medium text-gray-600">' . e(__('admin/modules/nova_poshta.sync.labels.processed_rows')) . '</th>';
        $html .= '</tr></thead><tbody class="divide-y divide-gray-200 bg-white">';

        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td class="px-4 py-3 text-gray-700">' . e((string) $row['label']) . '</td>';
            $html .= '<td class="px-4 py-3 text-right font-medium text-gray-900">' . e((string) $row['value']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    private function renderStatCard(string $label, int $value): string
    {
        return '
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500">' . e($label) . '</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">' . e((string) $value) . '</div>
            </div>
        ';
    }

    private function getStageLabel(string $stage): string
    {
        return match ($stage) {
            'regions' => (string) __('admin/modules/nova_poshta.sync.stages.regions'),
            'cities' => (string) __('admin/modules/nova_poshta.sync.stages.cities'),
            'post_offices' => (string) __('admin/modules/nova_poshta.sync.stages.post_offices'),
            'poshtomats' => (string) __('admin/modules/nova_poshta.sync.stages.poshtomats'),
            default => $stage !== '' ? $stage : '—',
        };
    }

    public function saveSettings(): void
    {
        $api_key = trim((string) Arr::get($this->settings_form, 'api_key', ''));

        if ($api_key === '') {
            throw ValidationException::withMessages([
                'settings_form.api_key' => __('admin/modules/nova_poshta.validation.api_key_required'),
            ]);
        }

        $delivery_cost = trim((string) Arr::get($this->settings_form, 'delivery_cost', ''));

        if ($delivery_cost === '') {
            throw ValidationException::withMessages([
                'settings_form.delivery_cost' => __('admin/modules/nova_poshta.validation.delivery_cost_required'),
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
            ->body(__('admin/modules/nova_poshta.notifications.settings_saved'))
            ->success()
            ->send();
    }

    private function getPhaseLabel(string $phase): string
    {
        return match ($phase) {
            'collect' => (string) __('admin/modules/nova_poshta.sync.phases.collect'),
            'finalize' => (string) __('admin/modules/nova_poshta.sync.phases.finalize'),
            'completed' => (string) __('admin/modules/nova_poshta.sync.states.completed'),
            'failed' => (string) __('admin/modules/nova_poshta.sync.states.failed'),
            default => $phase !== '' ? $phase : '—',
        };
    }
}
