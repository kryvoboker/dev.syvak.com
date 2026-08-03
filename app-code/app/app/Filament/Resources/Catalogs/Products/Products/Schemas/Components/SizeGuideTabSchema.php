<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Schemas\Components;

use App\Models\ApplicationSettings\Language;
use App\Supports\Services\Products\ProductSizeGuide;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class SizeGuideTabSchema
{
    /**
     * @param  Collection<int, Language>  $active_languages
     */
    public static function make(Collection $active_languages): Tab
    {
        return Tab::make(__('admin/catalogs/products/products.tabs.size_guide'))
            ->schema([
                Section::make(__('admin/catalogs/products/products.sections.size_guide'))
                    ->schema([
                        Tabs::make('ProductSizeGuideLanguageTabs')
                            ->tabs(self::buildLanguageTabs($active_languages))
                            ->activeTab(1)
                            ->contained(false)
                            ->persistTabInQueryString()
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  Collection<int, Language>  $active_languages
     * @return array<int, Tab>
     */
    private static function buildLanguageTabs(Collection $active_languages): array
    {
        $upload_max_size_kb = (int) config('app.images.product.upload.max_size_kb', 5120);
        $upload_max_size_mb = self::resolveMegabytesFromKilobytes($upload_max_size_kb);
        $upload_directory = resolve_upload_path_placeholders((string) config('app.images.product.image_path', 'images/products/{year}/{month}/'));

        return $active_languages
            ->map(function (Language $language) use ($upload_max_size_kb, $upload_max_size_mb, $upload_directory): Tab {
                $language_path = "size_guide_data.translations.$language->id";
                $table_path = "$language_path.table_rows";
                $image_path = "$language_path.image";

                return Tab::make((string) $language->name)
                    ->badge((string) $language->code)
                    ->schema([
                        TextInput::make("$language_path.title")
                            ->label(__('admin/catalogs/products/products.labels.size_guide_title'))
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make("$language_path.short_description")
                            ->label(__('admin/catalogs/products/products.labels.size_guide_short_description'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Textarea::make($table_path)
                            ->label(__('admin/catalogs/products/products.labels.size_guide_table'))
                            ->helperText(__('admin/catalogs/products/products.labels.size_guide_table_helper'))
                            ->rows(8)
                            ->live(debounce: '700ms')
                            ->columnSpanFull()
                            ->required(fn (Get $get): bool => self::mustRequireSizeTable($get, $table_path, $image_path))
                            ->rule(function (Get $get) use ($table_path, $image_path): Closure {
                                return function (string $attribute, mixed $value, Closure $fail) use ($get, $table_path, $image_path): void {
                                    if (! self::mustRequireSizeTable($get, $table_path, $image_path)) {
                                        return;
                                    }

                                    if (ProductSizeGuide::parseSizeGuideTableRowsFromString((string) $value) === []) {
                                        $fail(__('admin/catalogs/products/products.errors.validation_size_guide_table_required'));
                                    }
                                };
                            })
                            ->dehydrateStateUsing(fn (mixed $state): string => Str::trim((string) $state)),

                        TextEntry::make("$language_path.table_preview")
                            ->label(__('admin/catalogs/products/products.labels.size_guide_table_preview'))
                            ->state(function (Get $get) use ($table_path): HtmlString {
                                return self::renderTablePreview((string) $get($table_path));
                            })
                            ->columnSpanFull(),

                        FileUpload::make($image_path)
                            ->label(__('admin/catalogs/products/products.labels.size_guide_image'))
                            ->helperText(__('admin/default.helpers.max_upload_size_mb', ['size' => $upload_max_size_mb]))
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->directory($upload_directory)
                            ->maxSize($upload_max_size_kb)
                            ->preserveFilenames()
                            ->imageEditor()
                            ->columnSpanFull(),

                        Group::make([
                            TextInput::make("$language_path.image_width")
                                ->label(__('admin/default.labels.width'))
                                ->numeric()
                                ->minValue(1)
                                ->required(fn (Get $get): bool => filled((string) $get($image_path))),

                            TextInput::make("$language_path.image_height")
                                ->label(__('admin/default.labels.height'))
                                ->numeric()
                                ->minValue(1)
                                ->required(fn (Get $get): bool => filled((string) $get($image_path))),
                        ])
                        ->columns(),

                        TextInput::make("$language_path.full_description_title")
                            ->label(__('admin/catalogs/products/products.labels.size_guide_full_description_title'))
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make("$language_path.full_description")
                            ->label(__('admin/catalogs/products/products.labels.size_guide_full_description'))
                            ->rows(8)
                            ->maxLength(10000)
                            ->columnSpanFull(),
                    ])
                    ->columns(1);
            })
            ->values()
            ->all();
    }

    private static function mustRequireSizeTable(Get $get, string $table_path, string $image_path): bool
    {
        if (filled((string) $get($image_path))) {
            return true;
        }

        return Str::trim((string) $get($table_path)) !== '';
    }

    private static function resolveMegabytesFromKilobytes(int $kilobytes): string
    {
        return number_format(max(1, $kilobytes) / 1024, 2, '.', '');
    }

    private static function renderTablePreview(string $table_raw): HtmlString
    {
        $rows = ProductSizeGuide::parseSizeGuideTableRowsFromString($table_raw);

        if ($rows === []) {
            return new HtmlString('<p class="text-sm text-gray-500">' . e(__('admin/catalogs/products/products.labels.size_guide_table_preview_empty')) . '</p>');
        }

        $thead_cells = collect($rows[0] ?? [])
            ->map(fn (string $cell): string => '<th style="padding:6px 10px;border:1px solid #d1d5db;">' . e($cell) . '</th>')
            ->implode('');

        $tbody_rows = collect(array_slice($rows, 1))
            ->map(function (array $cells): string {
                $columns = collect($cells)
                    ->map(fn (string $cell): string => '<td style="padding:6px 10px;border:1px solid #d1d5db;">' . e($cell) . '</td>')
                    ->implode('');

                return '<tr>' . $columns . '</tr>';
            })
            ->implode('');

        $table_html = '<table style="border-collapse:collapse;width:100%;font-size:12px;">';

        if ($thead_cells !== '') {
            $table_html .= '<thead><tr>' . $thead_cells . '</tr></thead>';
        }

        if ($tbody_rows !== '') {
            $table_html .= '<tbody>' . $tbody_rows . '</tbody>';
        }

        $table_html .= '</table>';

        return new HtmlString($table_html);
    }
}
