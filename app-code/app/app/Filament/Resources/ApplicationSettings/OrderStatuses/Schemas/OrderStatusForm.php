<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\OrderStatuses\Schemas;

use App\Models\ApplicationSettings\Language;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderStatusForm
{
    public static function configure(Schema $schema): Schema
    {
        $active_languages = (new Language())->getActiveLanguages();
        $translation_tabs = [];

        foreach ($active_languages as $language) {
            $translation_tabs[] = Tabs\Tab::make($language->name)
                ->badge($language->code)
                ->schema([
                    TextInput::make("descriptions.$language->id.name")
                        ->label(__('admin/settings/order_statuses.labels.name'))
                        ->maxLength(255)
                        ->required(),
                ]);
        }

        return $schema
            ->components([
                Tabs::make('OrderStatusTabs')
                    ->tabs([
                        Tabs\Tab::make(__('admin/default.tabs.general'))
                            ->schema([
                                Section::make(__('admin/default.sections.basic_info'))
                                    ->schema([
                                        TextInput::make('code')
                                            ->label(__('admin/default.labels.code'))
                                            ->helperText(__('admin/settings/order_statuses.helpers.code'))
                                            ->maxLength(100)
                                            ->required()
                                            ->unique(ignoreRecord: true),

                                        Toggle::make('is_default')
                                            ->label(__('admin/default.labels.is_default'))
                                            ->helperText(__('admin/settings/order_statuses.helpers.is_default'))
                                            ->default(false)
                                            ->live()
                                            ->afterStateUpdated(function (bool $state, Set $set): void {
                                                if ($state) {
                                                    $set('is_active', true);
                                                }
                                            })
                                            ->required(),

                                        Toggle::make('is_active')
                                            ->label(__('admin/default.labels.is_active'))
                                            ->helperText(__('admin/settings/order_statuses.helpers.is_active'))
                                            ->default(true)
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),
                        Tabs\Tab::make(__('admin/default.tabs.translations'))
                            ->schema([
                                Tabs::make('OrderStatusLanguageTabs')
                                    ->tabs($translation_tabs)
                                    ->contained(false),
                            ])
                            ->visible($translation_tabs !== []),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}
