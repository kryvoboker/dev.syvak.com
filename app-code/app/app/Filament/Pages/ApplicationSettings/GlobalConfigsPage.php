<?php

declare(strict_types=1);

namespace App\Filament\Pages\ApplicationSettings;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Trait\TotalModelItemsResourceTrait;
use App\Models\ApplicationSettings\GlobalConfig;
use App\Supports\Services\GlobalConfigService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use UnitEnum;

class GlobalConfigsPage extends Page implements HasTable
{
    use InteractsWithTable, TotalModelItemsResourceTrait;

    protected static ?string $model = GlobalConfig::class;

    protected static ?string $slug = 'application-settings/global-configs';

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::ApplicationSettings;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CodeBracket;

    protected static ?int $navigationSort = 100;

    public function mount(): void
    {
        $this->mountInteractsWithTable();
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin/settings/global_configs.navigation_label');
    }

    public function getHeading(): string|Htmlable
    {
        return __('admin/settings/global_configs.navigation_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/settings/global_configs.navigation_label');
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getUrl() => __('admin/settings/global_configs.navigation_label'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => GlobalConfig::query())
            ->columns([
                TextInputColumn::make('key')
                    ->label(__('admin/settings/global_configs.labels.key'))
                    ->searchable()
                    ->sortable()
                    ->rules(fn (GlobalConfig $record): array => [
                        'required',
                        'string',
                        'max:191',
                        Rule::unique('global_configs', 'key')->ignore((int) $record->id),
                    ])
                    ->updateStateUsing(function (GlobalConfig $record, mixed $state): mixed {
                        return $this->saveGlobalConfigField($record, 'key', (string) $state);
                    }),

                TextInputColumn::make('value')
                    ->label(__('admin/settings/global_configs.labels.value'))
                    ->searchable()
                    ->sortable()
                    ->rules(['nullable', 'string'])
                    ->updateStateUsing(function (GlobalConfig $record, mixed $state): mixed {
                        return $this->saveGlobalConfigField($record, 'value', blank($state) ? null : (string) $state);
                    }),

                ToggleColumn::make('is_active')
                    ->label(__('admin/settings/global_configs.labels.is_active'))
                    ->sortable()
                    ->updateStateUsing(function (GlobalConfig $record, mixed $state): mixed {
                        return $this->saveGlobalConfigField($record, 'is_active', (bool) $state);
                    }),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/settings/global_configs.filters.status'))
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/default.filters.active_only'))
                    ->falseLabel(__('admin/default.filters.inactive_only')),

                Filter::make('key')
                    ->label(__('admin/settings/global_configs.filters.key'))
                    ->schema([
                        TextInput::make('key')
                            ->label(__('admin/settings/global_configs.labels.key'))
                            ->placeholder(__('admin/settings/global_configs.placeholders.key')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $key = trim((string) ($data['key'] ?? ''));

                        if ($key === '') {
                            return $query;
                        }

                        return $query->where('key', 'like', '%' . $key . '%');
                    }),

                Filter::make('value')
                    ->label(__('admin/settings/global_configs.filters.value'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('admin/settings/global_configs.labels.value'))
                            ->placeholder(__('admin/settings/global_configs.placeholders.value')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = trim((string) ($data['value'] ?? ''));

                        if ($value === '') {
                            return $query;
                        }

                        return $query->where('value', 'like', '%' . $value . '%');
                    }),
            ])
            ->searchPlaceholder(__('admin/settings/global_configs.placeholders.search'))
            ->headerActions([
                Action::make('create_config')
                    ->label(__('admin/settings/global_configs.actions.create'))
                    ->icon(Heroicon::Plus)
                    ->schema([
                        TextInput::make('key')
                            ->label(__('admin/settings/global_configs.labels.key'))
                            ->required()
                            ->maxLength(191)
                            ->rules([
                                'required',
                                'string',
                                'max:191',
                                Rule::unique('global_configs', 'key'),
                            ]),

                        Textarea::make('value')
                            ->label(__('admin/settings/global_configs.labels.value'))
                            ->rows(4)
                            ->default(null)
                            ->nullable(),

                        Toggle::make('is_active')
                            ->label(__('admin/settings/global_configs.labels.is_active'))
                            ->default(true),
                    ])
                    ->modalHeading(__('admin/settings/global_configs.actions.create'))
                    ->modalSubmitActionLabel(__('admin/default.buttons.create'))
                    ->action(function (array $data): void {
                        app(GlobalConfigService::class)->saveGlobalConfig(null, [
                            'key' => (string) $data['key'],
                            'value' => array_key_exists('value', $data) && $data['value'] !== '' ? (string) $data['value'] : null,
                            'is_active' => (bool) ($data['is_active'] ?? true),
                        ]);

                        $this->resetTable();

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/settings/global_configs.notifications.saved_single'))
                            ->success()
                            ->send();
                    }),
                Action::make('delete_all_configs')
                    ->label(__('admin/settings/global_configs.actions.delete_all'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        $deleted_count = app(GlobalConfigService::class)->deleteAllGlobalConfigs();

                        $this->resetTable();

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/settings/global_configs.notifications.deleted_all', [
                                'deleted_count' => $deleted_count,
                            ]))
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('disable_selected_configs')
                        ->label(__('admin/settings/global_configs.actions.disable_selected'))
                        ->color('warning')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $disabled_count = app(GlobalConfigService::class)->disableGlobalConfigsByIds($records->modelKeys());

                            $this->resetTable();

                            Notification::make()
                                ->title(__('admin/default.success.title'))
                                ->body(__('admin/settings/global_configs.notifications.disabled_selected', [
                                    'updated_count' => $disabled_count,
                                ]))
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('delete_selected_configs')
                        ->label(__('admin/settings/global_configs.actions.delete_selected'))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $deleted_count = app(GlobalConfigService::class)->deleteGlobalConfigsByIds($records->modelKeys());

                            $this->resetTable();

                            Notification::make()
                                ->title(__('admin/default.success.title'))
                                ->body(__('admin/settings/global_configs.notifications.deleted_selected', [
                                    'deleted_count' => $deleted_count,
                                ]))
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('key')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [];
    }

    private function saveGlobalConfigField(GlobalConfig $record, string $field, mixed $value): mixed
    {
        $data = [
            $field => $value,
        ];

        if ($field !== 'key') {
            $data['key'] = (string) $record->key;
        }

        if ($field !== 'value') {
            $data['value'] = $record->value;
        } else {
            $data['value'] = $value === '' ? null : (string) $value;
        }

        if ($field !== 'is_active') {
            $data['is_active'] = (bool) $record->is_active;
        }

        app(GlobalConfigService::class)->saveGlobalConfig($record, $data);

        return $value;
    }
}
