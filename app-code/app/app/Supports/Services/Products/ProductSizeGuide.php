<?php

declare(strict_types=1);

namespace App\Supports\Services\Products;

use Illuminate\Support\Str;

class ProductSizeGuide
{
    /**
     * @return array<int, array<int, string>>
     */
    public static function parseSizeGuideTableRowsFromString(string $table_raw): array
    {
        $table_raw = Str::trim($table_raw);

        if ($table_raw === '') {
            return [];
        }

        $rows = preg_split('/\R/u', $table_raw) ?: [];

        return collect($rows)
            ->map(function (string $row): array {
                $trimmed_row = Str::trim($row);

                if ($trimmed_row === '') {
                    return [];
                }

                if (str_contains($trimmed_row, "\t")) {
                    $cells = explode("\t", $trimmed_row);
                } elseif (str_contains($trimmed_row, ';')) {
                    $cells = str_getcsv($trimmed_row, ';');
                } else {
                    $cells = str_getcsv($trimmed_row, ';');
                }

                return collect($cells)
                    ->map(fn (string $cell): string => Str::trim($cell))
                    ->values()
                    ->all();
            })
            ->filter(fn (array $cells): bool => $cells !== [])
            ->values()
            ->all();
    }
}
