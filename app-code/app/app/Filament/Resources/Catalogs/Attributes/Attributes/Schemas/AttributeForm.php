<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes\Schemas;

use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\ApplicationSettings\Language;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;

class AttributeForm
{
    use LanguageTrait;

    public static function configure(Schema $schema): Schema
    {
        $active_languages = self::getActiveLanguages();

        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Tabs::make('LanguageTabs')
                            ->tabs(self::createLanguageTabs($active_languages))
                            ->activeTab(1)
                            ->contained(false)
                            ->persistTabInQueryString(),

                        Toggle::make('is_active')
                            ->label(__('admin/default.labels.is_active'))
                            ->default(true)
                            ->required(),

                        TextInput::make('sort_order')
                            ->label(__('admin/default.labels.sort_order'))
                            ->numeric()
                            ->rules(['numeric', 'min:0'])
                            ->default(1)
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Create language tabs for specific section
     *
     * @param  Collection<Language>  $active_languages
     * @return array<Tabs\Tab>
     */
    protected static function createLanguageTabs(Collection $active_languages): array
    {
        $tabs = [];

        foreach ($active_languages as $language) {
            $tabs[] = Tabs\Tab::make($language->name)
                ->schema([
                    Hidden::make("descriptions.$language->id.language_id")
                        ->default($language->id),

                    TextInput::make("descriptions.$language->id.name")
                        ->label(__('admin/default.labels.name'))
                        ->maxLength(255)
                        ->rules(['required', 'string', 'max:255'])
                        ->required(),
                ])
                ->badge($language->code);
        }

        return $tabs;
    }
}
