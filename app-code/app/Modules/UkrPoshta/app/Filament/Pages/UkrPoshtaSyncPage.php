<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Filament\Pages;

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
use Illuminate\Validation\ValidationException;
use Modules\UkrPoshta\Models\UkrPoshtaCity;
use Modules\UkrPoshta\Models\UkrPoshtaDistrict;
use Modules\UkrPoshta\Models\UkrPoshtaPostOffice;
use Modules\UkrPoshta\Models\UkrPoshtaRegion;
use Modules\UkrPoshta\Services\UkrPoshtaSyncService;
use Modules\UkrPoshta\Support\UkrPoshtaConfig;
use Throwable;
use UnitEnum;

class UkrPoshtaSyncPage extends Page
{
    protected static ?string $slug = 'modules/ukr-poshta';

    // phpcs:ignore
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowPath;

    protected static string|null|UnitEnum $navigationGroup = null;

    public array $settings_form = [];

    public array $sync_state = [];

    public function mount(UkrPoshtaConfig $ukr_poshta_config): void
    {
        $this->settings_form = [
            'api_key' => trim((string)get_global_config(UkrPoshtaConfig::API_KEY_GLOBAL_CONFIG_KEY, '')),
            'delivery_cost' => (string)get_global_config(UkrPoshtaConfig::DELIVERY_COST_GLOBAL_CONFIG_KEY, $ukr_poshta_config->getDeliveryCost()),
            'is_delivery_cost_enabled' => $ukr_poshta_config->isDeliveryCostEnabled(),
        ];
        $this->sync_state = app(UkrPoshtaSyncService::class)->getQueuedSyncState();
    }

    public function getTitle(): string
    {
        return __('admin/modules/ukr_poshta.title');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        return __('admin/modules/ukr_poshta.description');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/modules/ukr_poshta.navigation_label');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin/modules/ukr_poshta.sections.shared.title'))
                ->description(__('admin/modules/ukr_poshta.sections.shared.description'))
                ->columnSpanFull()
                ->statePath('settings_form')
                ->schema([
                    TextInput::make('api_key')
                        ->label(__('admin/modules/ukr_poshta.labels.api_key'))
                        ->password()
                        ->revealable()
                        ->required()
                        ->helperText(__('admin/modules/ukr_poshta.helpers.api_key')),
                    TextInput::make('delivery_cost')
                        ->label(__('admin/modules/ukr_poshta.labels.delivery_cost'))
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default('0.00')
                        ->step(0.01)
                        ->prefix('₴')
                        ->helperText(__('admin/modules/ukr_poshta.helpers.delivery_cost')),
                    Toggle::make('is_delivery_cost_enabled')
                        ->label(__('admin/modules/ukr_poshta.labels.is_delivery_cost_enabled'))
                        ->helperText(__('admin/modules/ukr_poshta.helpers.is_delivery_cost_enabled'))
                        ->default(false),
                ])
                ->footerActions([
                    Action::make('saveSettings')
                        ->label(__('admin/modules/ukr_poshta.actions.save_settings'))
                        ->icon(Heroicon::CheckCircle)
                        ->action(function (): void {
                            $this->saveSettings();
                        }),
                ]),
            Section::make(__('admin/modules/ukr_poshta.sections.sync.title'))
                ->description(__('admin/modules/ukr_poshta.sections.sync.description'))
                ->columnSpanFull()
                ->visible(fn (): bool => (bool)Arr::get($this->sync_state, 'is_running', false))
                ->schema([
                    Html::make(fn (): string => $this->renderSyncWorkspace()),
                ]),
            Section::make(__('admin/modules/ukr_poshta.sections.summary.title'))
                ->columnSpanFull()
                ->schema([
                    Html::make(fn (): string => $this->renderLastSyncSummary()),
                ]),
        ]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncUkrPoshta')
                ->label(__('admin/modules/ukr_poshta.actions.sync'))
                ->icon(Heroicon::ArrowPath)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('admin/modules/ukr_poshta.actions.sync'))
                ->modalDescription(__('admin/modules/ukr_poshta.actions.sync_confirmation'))
                ->action(function (): void {
                    $this->startSync();
                }),
            Action::make('stopUkrPoshtaSync')
                ->label(__('admin/modules/ukr_poshta.actions.stop_sync'))
                ->icon(Heroicon::XMark)
                ->color('warning')
                ->visible(fn (): bool => (bool)Arr::get($this->sync_state, 'is_running', false))
                ->action(function (): void {
                    $this->stopSync();
                }),
        ];
    }

    public function processSyncStep(): void
    {
        $previous_state = $this->sync_state;
        $sync_service = app(UkrPoshtaSyncService::class);

        try {
            $this->sync_state = $sync_service->processQueuedSyncStep();

            $was_running = (bool)Arr::get($previous_state, 'is_running', false);
            $is_running = (bool)Arr::get($this->sync_state, 'is_running', false);
            $stage = (string)Arr::get($this->sync_state, 'stage', '');

            if ($was_running && !$is_running) {
                $notification = Notification::make()
                    ->title(__('admin/default.success.title'))
                    ->body(match ($stage) {
                        'completed' => __('admin/modules/ukr_poshta.notifications.sync_completed'),
                        'stopped' => __('admin/modules/ukr_poshta.notifications.sync_stopped'),
                        default => __('admin/modules/ukr_poshta.notifications.sync_failed'),
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
                ->body(__('admin/modules/ukr_poshta.notifications.sync_failed'))
                ->danger()
                ->send();
        }
    }

    private function startSync(): void
    {
        try {
            $sync_service = app(UkrPoshtaSyncService::class);
            $this->sync_state = $sync_service->startQueuedSync();
            $this->sync_state = $sync_service->processQueuedSyncStep();

            Notification::make()
                ->title(__('admin/default.success.title'))
                ->body(__('admin/modules/ukr_poshta.notifications.sync_started'))
                ->info()
                ->send();
        } catch (Throwable $throwable) {
            report($throwable);

            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/modules/ukr_poshta.notifications.sync_failed'))
                ->danger()
                ->send();
        }
    }

    private function stopSync(): void
    {
        try {
            $sync_service = app(UkrPoshtaSyncService::class);
            $this->sync_state = $sync_service->requestQueuedSyncStop();
            $sync_service->forgetQueuedSyncState();

            Notification::make()
                ->title(__('admin/default.success.title'))
                ->body(__('admin/modules/ukr_poshta.notifications.stop_requested'))
                ->warning()
                ->send();
        } catch (Throwable $throwable) {
            report($throwable);

            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/modules/ukr_poshta.notifications.sync_failed'))
                ->danger()
                ->send();
        }
    }

    private function renderSyncWorkspace(): string
    {
        $sync_state = $this->sync_state;
        $is_running = (bool)Arr::get($sync_state, 'is_running', false);
        $message = (string)Arr::get($sync_state, 'message', '');
        $stage = (string)Arr::get($sync_state, 'stage', '');
        $phase = (string)Arr::get($sync_state, 'phase', '');
        $status_label = match ($stage) {
            'completed' => __('admin/modules/ukr_poshta.sync.states.completed'),
            'failed' => __('admin/modules/ukr_poshta.sync.states.failed'),
            'stopped' => __('admin/modules/ukr_poshta.sync.states.stopped'),
            default => $is_running ? __('admin/modules/ukr_poshta.sync.states.running') : __('admin/modules/ukr_poshta.sync.states.idle'),
        };
        $stage_label = $this->getStageLabel($stage);
        $polling_attribute = $is_running ? ' wire:poll.2s="processSyncStep"' : '';
        $sync_summary_rows = $this->getDatabaseStateRows();
        $stage_processed_rows = (string)Arr::get($sync_state, 'stage_processed_rows', 0);
        $regions_card = $this->renderStatCard(
            __('admin/modules/ukr_poshta.stats.regions'),
            (int)data_get($sync_summary_rows, 'regions.count', 0),
        );
        $districts_card = $this->renderStatCard(
            __('admin/modules/ukr_poshta.stats.districts'),
            (int)data_get($sync_summary_rows, 'districts.count', 0),
        );
        $cities_card = $this->renderStatCard(
            __('admin/modules/ukr_poshta.stats.cities'),
            (int)data_get($sync_summary_rows, 'cities.count', 0),
        );
        $post_offices_card = $this->renderStatCard(
            __('admin/modules/ukr_poshta.stats.post_offices'),
            (int)data_get($sync_summary_rows, 'post_offices.count', 0),
        );

        return '
            <div class="space-y-6"' . $polling_attribute . '>
                <div class="rounded-xl border p-6 shadow-sm">
                    <div class="flex flex-col gap-6">
                        <div class="max-w-3xl space-y-1">
                            <div class="flex items-center gap-3">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em]">' . e(__('admin/modules/ukr_poshta.sync.labels.status')) . '</div>
                                <div>-</div>
                                <div class="rounded-2xl border-2 border-green-700 bg-green-600 px-2 py-1 text-2xl font-semibold">' . e($status_label) . '</div>
                            </div>
                            <p class="block text-sm leading-6">' . e($message !== '' ? $message : __('admin/modules/ukr_poshta.sections.sync.description')) . '</p>
                        </div>

                        <div class="w-full rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="relative flex h-12 w-12 shrink-0 items-center justify-center" aria-hidden="true">
                                    <span class="absolute inset-0 rounded-full border-4 border-emerald-200 border-t-emerald-600 animate-spin"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                </div>
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="text-sm font-semibold text-emerald-950">' . e(__('admin/modules/ukr_poshta.sync.states.running')) . '</div>
                                    <div class="text-sm text-emerald-900/80">' . e(__('admin/modules/ukr_poshta.sync.labels.stage')) . ': ' . e($stage_label) . '</div>
                                    <div class="text-sm text-emerald-900/80">' . e(__('admin/modules/ukr_poshta.sync.labels.phase')) . ': ' . e($this->getPhaseLabel($phase)) . '</div>
                                    <div class="text-sm text-emerald-900/80">' . e(__('admin/modules/ukr_poshta.sync.labels.processed_rows')) . ': ' . e($stage_processed_rows) . '</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    ' . $regions_card . '
                    ' . $districts_card . '
                    ' . $cities_card . '
                    ' . $post_offices_card . '
                </div>
            </div>
        ';
    }

    /**
     * @return string
     */
    private function renderLastSyncSummary(): string
    {
        return $this->renderCurrentStateTable(
            $this->getDatabaseStateRows(),
            __('admin/modules/ukr_poshta.stats.stored'),
        );
    }

    private function renderStatCard(string $label, int $processed): string
    {
        return '
            <div class="rounded-lg border p-4">
                <div class="text-xs uppercase tracking-wide">' . e($label) . '</div>
                <div class="mt-2 text-2xl font-semibold">' . e((string)$processed) . '</div>
            </div>
        ';
    }

    /**
     * @return array<string, array{label:string,count:int}>
     */
    private function getDatabaseStateRows(): array
    {
        return [
            'regions' => [
                'label' => __('admin/modules/ukr_poshta.stats.regions'),
                'count' => UkrPoshtaRegion::query()->count(),
            ],
            'districts' => [
                'label' => __('admin/modules/ukr_poshta.stats.districts'),
                'count' => UkrPoshtaDistrict::query()->count(),
            ],
            'cities' => [
                'label' => __('admin/modules/ukr_poshta.stats.cities'),
                'count' => UkrPoshtaCity::query()->count(),
            ],
            'post_offices' => [
                'label' => __('admin/modules/ukr_poshta.stats.post_offices'),
                'count' => UkrPoshtaPostOffice::query()->count(),
            ],
        ];
    }

    /**
     * @param array<string, array{label:string,count:int}> $rows
     */
    private function renderCurrentStateTable(array $rows, string $column_label): string
    {
        if ($rows === []) {
            return '<p class="text-sm text-gray-600">' . e(__('admin/modules/ukr_poshta.sections.summary.empty')) . '</p>';
        }

        $html = '<div class="overflow-hidden rounded-xl border shadow-sm">';
        $html .= '<table class="min-w-full divide-y text-sm">';
        $html .= '<thead class="border-b"><tr>';
        $html .= '<th class="px-4 py-3 text-left font-medium">' . e(__('admin/modules/ukr_poshta.sync.labels.stage')) . '</th>';
        $html .= '<th class="px-4 py-3 text-right font-medium">' . e($column_label) . '</th>';
        $html .= '</tr></thead><tbody class="divide-y">';

        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td class="px-4 py-3">' . e((string)$row['label']) . '</td>';
            $html .= '<td class="px-4 py-3 text-right font-medium">' . e((string)$row['count']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    private function getStageLabel(string $stage): string
    {
        return match ($stage) {
            'regions' => (string)__('admin/modules/ukr_poshta.sync.stages.regions'),
            'districts' => (string)__('admin/modules/ukr_poshta.sync.stages.districts'),
            'cities' => (string)__('admin/modules/ukr_poshta.sync.stages.cities'),
            'post_offices' => (string)__('admin/modules/ukr_poshta.sync.stages.post_offices'),
            default => $stage !== '' ? $stage : '—',
        };
    }

    private function getPhaseLabel(string $phase): string
    {
        return match ($phase) {
            'collect' => (string)__('admin/modules/ukr_poshta.sync.phases.collect'),
            'finalize' => (string)__('admin/modules/ukr_poshta.sync.phases.finalize'),
            'completed' => (string)__('admin/modules/ukr_poshta.sync.states.completed'),
            'failed' => (string)__('admin/modules/ukr_poshta.sync.states.failed'),
            default => $phase !== '' ? $phase : '—',
        };
    }

    public function saveSettings(): void
    {
        $api_key = trim((string)Arr::get($this->settings_form, 'api_key', ''));

        if ($api_key === '') {
            throw ValidationException::withMessages([
                'settings_form.api_key' => __('admin/modules/ukr_poshta.validation.api_key_required'),
            ]);
        }

        $delivery_cost = trim((string)Arr::get($this->settings_form, 'delivery_cost', ''));

        if ($delivery_cost === '') {
            throw ValidationException::withMessages([
                'settings_form.delivery_cost' => __('admin/modules/ukr_poshta.validation.delivery_cost_required'),
            ]);
        }

        set_global_config([
            UkrPoshtaConfig::API_KEY_GLOBAL_CONFIG_KEY => [
                'value' => $api_key,
                'is_active' => true,
            ],
            UkrPoshtaConfig::DELIVERY_COST_GLOBAL_CONFIG_KEY => [
                'value' => number_format((float)$delivery_cost, 2, '.', ''),
                'is_active' => true,
            ],
            UkrPoshtaConfig::IS_DELIVERY_COST_ENABLED_GLOBAL_CONFIG_KEY => [
                'value' => (bool)Arr::get($this->settings_form, 'is_delivery_cost_enabled', false),
                'is_active' => true,
            ],
        ]);

        Notification::make()
            ->title(__('admin/default.success.title'))
            ->body(__('admin/modules/ukr_poshta.notifications.settings_saved'))
            ->success()
            ->send();
    }
}
