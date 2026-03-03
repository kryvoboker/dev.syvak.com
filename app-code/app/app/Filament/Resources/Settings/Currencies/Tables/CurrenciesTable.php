<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Currencies\Tables;

use App\Filament\Resources\Trait\Filters\BooleanFilterTrait;
use App\Filament\Resources\Trait\Tables\BooleanTableTrait;
use App\Filament\Resources\Trait\Tables\CommonTextTableTrait;
use App\Filament\Resources\Trait\Tables\DateTableTrait;
use App\Models\Settings\Currency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CurrenciesTable
{
    use CommonTextTableTrait, BooleanTableTrait, DateTableTrait, BooleanFilterTrait;

    /**
     * @param Table $table
     *
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                self::getCodeTableField(),

                self::getTextTableField([
                    'field_name' => 'name',
                    'label'      => __('admin/default.columns.name'),
                ]),

                self::getTextTableField([
                    'field_name'                   => 'format_locale',
                    'label'                        => __('admin/default.columns.format_locale'),
                    'is_toggled_hidden_by_default' => true,
                ]),

                self::getTextTableField([
                    'field_name'                   => 'symbol_left',
                    'label'                        => __('admin/settings/currencies.columns.symbol_left'),
                    'is_toggled_hidden_by_default' => true,
                ]),

                self::getTextTableField([
                    'field_name'                   => 'symbol_right',
                    'label'                        => __('admin/settings/currencies.columns.symbol_right'),
                    'is_toggled_hidden_by_default' => true,
                ]),

                self::getTextTableField([
                    'field_name'                   => 'decimal_places',
                    'label'                        => __('admin/settings/currencies.columns.decimal_places'),
                    'is_toggled_hidden_by_default' => true,
                ]),

                self::getTextTableField([
                    'field_name' => 'exchange_rate',
                    'label'      => __('admin/settings/currencies.columns.exchange_rate'),
                ]),

                self::getIsActiveTableField(),

                self::getIsDefaultTableField(),

                self::getCreatedAtTableField([
                    'is_toggled_hidden_by_default' => false,
                ]),
            ])
            ->filters([
                self::getIsActiveFilterField(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, Collection $records) {
                            // Check if trying to delete default currency
                            $has_default = $records->contains('is_default', true);

                            if ($has_default) {
                                Notification::make()
                                    ->title(__('admin/default.errors.title'))
                                    ->body(__('admin/settings/currencies.errors.cant_delete_default_currency'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }

                            // Check if trying to delete all active currencies
                            $active_to_delete = $records->where('is_active', true)->count();
                            $total_active     = Currency::where('is_active', true)->count();

                            if ($active_to_delete >= $total_active) {
                                Notification::make()
                                    ->title(__('admin/default.errors.title'))
                                    ->body(__('admin/settings/currencies.errors.cant_delete_last_active_currency'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }
}
