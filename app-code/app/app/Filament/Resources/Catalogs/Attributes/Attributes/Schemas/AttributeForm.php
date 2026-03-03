<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes\Schemas;

use App\Filament\Resources\Trait\Forms\CommonTextFormTrait;
use App\Filament\Resources\Trait\Forms\SortOrderFormTrait;
use App\Filament\Resources\Trait\Forms\ToggleCheckboxFormTrait;
use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\Settings\Language;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;

class AttributeForm
{
    use LanguageTrait, CommonTextFormTrait, ToggleCheckboxFormTrait, SortOrderFormTrait;

    /**
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getAcriveLanguages();

        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Tabs::make('LanguageTabs')
                            ->tabs(self::createLanguageTabs($active_languages))
                            ->activeTab(1)
                            ->contained(false)
                            ->persistTabInQueryString(),

                        self::getIsActiveFormField(),

                        self::getSortOrderFormField(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Create language tabs for specific section
     *
     * @param Collection<Language> $active_languages
     *
     * @return array<Tabs>
     */
    protected static function createLanguageTabs(Collection $active_languages): array
    {
        $tabs = [];

        foreach ($active_languages as $language) {
            $tabs[] = Tabs\Tab::make($language->name)
                ->schema([
                    Hidden::make("descriptions.$language->id.language_id")
                        ->default($language->id),

                    self::getTextFormField([
                        'field_name' => "descriptions.$language->id.name",
                        'label'      => __('admin/default.labels.name'),
                        'rules'      => ['required', 'string', 'max:255'],
                    ]),
                ])
                ->badge($language->code);
        }

        return $tabs;
    }
}
