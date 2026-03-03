<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes\Tables;

use App\Filament\Resources\Trait\LanguageTrait;
use App\Filament\Resources\Trait\Tables\BooleanTableTrait;
use App\Filament\Resources\Trait\Tables\CommonTextTableTrait;
use App\Filament\Resources\Trait\Tables\DateTableTrait;
use App\Filament\Resources\Trait\Tables\NumericTableTrait;
use App\Models\Catalogs\Attributes\Attribute;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttributesTable
{
    use LanguageTrait, CommonTextTableTrait, NumericTableTrait, BooleanTableTrait, DateTableTrait;

    /**
     * @param Table $table
     *
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        $current_language_id = self::getCurrentLanguageId();

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Eager load descriptions to avoid N+1 problem
                return $query->with('attributeDescription');
            })
            ->columns([
                self::getTextTableField([
                    'field_name'         => 'attributeDescription.name',
                    'label'              => __('admin/default.columns.name'),
                    'searchable'         => ['name'],
                    'get_state_using_cb' => function (Attribute $record) use ($current_language_id) {
                        if (($returned_value = self::validateLanguageIdIsNotNull($current_language_id)) !== null) {
                            return $returned_value;
                        }

                        // Try to get description for current locale
                        $description = $record->attributeDescription
                            ->firstWhere('language_id', $current_language_id);

                        // Fallback to first available description
                        if (!$description) {
                            $description = $record->attributeDescription->first();
                        }

                        return $description?->name ?? '-';
                    },
                ]),

                self::getNumericTableField([
                    'field_name' => 'sort_order',
                    'label'      => __('admin/default.columns.sort_order'),
                ]),

                self::getIsActiveTableField(),

                self::getCreatedAtTableField(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
