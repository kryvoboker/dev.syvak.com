<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Schemas;

use App\Filament\Resources\Catalogs\Products\Products\Schemas\Components\SizeGuideTabSchema;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Users\UserGroup;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        $active_languages = (new Language())->getActiveLanguages();

        return $schema
            ->components([
                Tabs::make('ProductVariantTabs')
                    ->tabs([
                        Tab::make(__('admin/default.tabs.general'))
                            ->schema([
                                Section::make('Variant')
                                    ->schema([
                                        Toggle::make('is_default')
                                            ->label('Default variant')
                                            ->default(false)
                                            ->required(),

                                        Toggle::make('is_active')
                                            ->default(true)
                                            ->required(),

                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),

                                        TextInput::make('minimum')
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),

                                        TextInput::make('price')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),

                                        FileUpload::make('image')
                                            ->image()
                                            ->directory(resolve_upload_path_placeholders((string) config('app.images.product.image_path')))
                                            ->nullable(),

                                        DateTimePicker::make('date_available')
                                            ->default(now(config('app.timezone'))),

                                        TextInput::make('sort_order')
                                            ->numeric()
                                            ->default(0)
                                            ->required(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/default.tabs.translations'))
                            ->schema([
                                Section::make('Variant descriptions')
                                    ->schema([
                                        Tabs::make('ProductVariantTranslationsLanguageTabs')
                                            ->tabs(self::buildTranslationTabs($active_languages))
                                            ->activeTab(1)
                                            ->contained(false)
                                            ->persistTabInQueryString(),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),
                            ]),

                        SizeGuideTabSchema::make($active_languages),

                        Tab::make(__('admin/default.tabs.images'))
                            ->schema([
                                Section::make('Variant images')
                                    ->schema([
                                        Repeater::make('images')
                                            ->relationship('images')
                                            ->schema([
                                                FileUpload::make('image')
                                                    ->image()
                                                    ->directory(resolve_upload_path_placeholders((string) config('app.images.product.image_path')))
                                                    ->required(),
                                                Toggle::make('is_primary')
                                                    ->default(false),
                                                TextInput::make('sort_order')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/default.tabs.discounts'))
                            ->schema([
                                Section::make('Variant discounts')
                                    ->schema([
                                        Repeater::make('discounts')
                                            ->relationship('discounts')
                                            ->schema([
                                                Select::make('user_group_id')
                                                    ->options(UserGroup::query()->where('is_active', true)->pluck('name', 'id')->all())
                                                    ->searchable()
                                                    ->required(),
                                                TextInput::make('quantity')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                                TextInput::make('priority')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                                TextInput::make('price')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->required(),
                                                DateTimePicker::make('date_start')
                                                    ->required(),
                                                DateTimePicker::make('date_end')
                                                    ->required(),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/default.tabs.attributes'))
                            ->schema([
                                Section::make('Variant attributes')
                                    ->schema([
                                        Tabs::make('ProductVariantAttributesLanguageTabs')
                                            ->tabs(self::buildAttributeTabs($active_languages))
                                            ->activeTab(1)
                                            ->contained(false)
                                            ->persistTabInQueryString(),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/default.tabs.slugs'))
                            ->schema([
                                Section::make('Variant slugs')
                                    ->schema([
                                        Repeater::make('slugs')
                                            ->relationship('slugs')
                                            ->schema([
                                                Select::make('language_id')
                                                    ->label(__('admin/default.labels.language'))
                                                    ->options(Language::query()->pluck('name', 'id')->all())
                                                    ->searchable()
                                                    ->required(),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->maxLength(500),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->rule(function (): Closure {
                                                return function (string $attribute, mixed $value, Closure $fail): void {
                                                    if (! is_array($value) || $value === []) {
                                                        return;
                                                    }

                                                    $language_ids = collect($value)
                                                        ->filter(fn (mixed $row): bool => is_array($row))
                                                        ->map(fn (array $row): int => (int) ($row['language_id'] ?? 0))
                                                        ->filter(fn (int $language_id): bool => $language_id > 0);

                                                    if ($language_ids->count() !== $language_ids->unique()->count()) {
                                                        $fail(__('admin/catalogs/products/products.errors.duplicate_variant_slug_language'));
                                                    }
                                                };
                                            }),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  Collection<Language>  $active_languages
     * @return array<Tab>
     */
    private static function buildTranslationTabs(Collection $active_languages): array
    {
        $tabs = [];

        foreach ($active_languages as $language) {
            /** @var Language $language */
            $tabs[] = Tab::make((string) $language->name)
                ->badge((string) $language->code)
                ->schema([
                    Repeater::make("descriptions_language_{$language->id}")
                        ->relationship(
                            'descriptions',
                            fn (Builder $query): Builder => $query->where('language_id', (int) $language->id),
                        )
                        ->schema([
                            Hidden::make('language_id')
                                ->default((int) $language->id),

                            TextInput::make('name')
                                ->label(__('admin/default.labels.name'))
                                ->maxLength(255)
                                ->nullable()
                                ->columnSpanFull(),

                            TextInput::make('meta_title')
                                ->label(__('admin/default.labels.meta_title'))
                                ->maxLength(255)
                                ->nullable()
                                ->columnSpanFull(),

                            Textarea::make('meta_description')
                                ->label(__('admin/default.labels.meta_description'))
                                ->rows(4)
                                ->maxLength(255)
                                ->nullable()
                                ->columnSpanFull(),

                            TextInput::make('meta_keywords')
                                ->label(__('admin/default.labels.meta_keywords'))
                                ->maxLength(255)
                                ->nullable()
                                ->columnSpanFull(),

                            Textarea::make('description')
                                ->label(__('admin/default.labels.description'))
                                ->rows(8)
                                ->maxLength(5000)
                                ->nullable()
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]);
        }

        return $tabs;
    }

    /**
     * @param  Collection<Language>  $active_languages
     * @return array<Tab>
     */
    private static function buildAttributeTabs(Collection $active_languages): array
    {
        $tabs = [];

        foreach ($active_languages as $language) {
            /** @var Language $language */
            $tabs[] = Tab::make((string) $language->name)
                ->badge((string) $language->code)
                ->schema([
                    Repeater::make("attribute_values_language_{$language->id}")
                        ->relationship(
                            'attributeValues',
                            fn (Builder $query): Builder => $query->where('language_id', (int) $language->id),
                        )
                        ->schema([
                            Hidden::make('language_id')
                                ->default((int) $language->id),

                            Select::make('attribute_id')
                                ->label(__('admin/default.labels.attribute'))
                                ->options(self::resolveAttributeOptions((int) $language->id))
                                ->searchable()
                                ->required(),

                            TextInput::make('value_string')
                                ->label(__('admin/default.labels.attribute_text'))
                                ->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]);
        }

        return $tabs;
    }

    /**
     * @return array<int, string>
     */
    private static function resolveAttributeOptions(int $language_id): array
    {
        return Attribute::query()
            ->where('is_active', true)
            ->with([
                'attributeDescription' => function ($query) use ($language_id): void {
                    $query->orderByRaw('language_id = ? desc', [$language_id])
                        ->orderBy('id');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function (Attribute $attribute): array {
                $label = (string) data_get(
                    $attribute->attributeDescription->first(),
                    'name',
                    "Attribute #{$attribute->id}",
                );

                return [
                    (int) $attribute->id => $label,
                ];
            })
            ->all();
    }
}
