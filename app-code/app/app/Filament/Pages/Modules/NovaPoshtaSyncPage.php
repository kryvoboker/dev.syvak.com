<?php

declare(strict_types=1);

namespace App\Filament\Pages\Modules;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Pages\Wiki\ModulesWikiPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Modules\NovaPoshta\Services\NovaPoshtaSyncService;
use Throwable;
use UnitEnum;

class NovaPoshtaSyncPage extends Page
{
    protected static ?string $slug = 'modules/nova-poshta';

    // phpcs:ignore
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowPath;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Modules;

    protected string $view = 'filament.pages.modules.nova-poshta-sync-page';

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncNovaPoshta')
                ->label(__('admin/modules/nova_poshta.actions.sync'))
                ->icon(Heroicon::ArrowPath)
                ->requiresConfirmation()
                ->modalHeading(__('admin/modules/nova_poshta.actions.sync'))
                ->modalDescription(__('admin/modules/nova_poshta.actions.sync_confirmation'))
                ->action(function (): void {
                    try {
                        $summary = app(NovaPoshtaSyncService::class)->syncAll();

                        Cache::put('nova_poshta.last_sync_summary', $summary, now()->addDay());

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/modules/nova_poshta.notifications.sync_completed'))
                            ->success()
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

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'last_sync_summary' => Cache::get('nova_poshta.last_sync_summary', []),
        ];
    }
}
